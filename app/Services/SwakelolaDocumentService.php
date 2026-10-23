<?php
require_once __DIR__.'/PageSetupService.php';
require_once __DIR__.'/OfficialLetterheadPdfService.php';

/** Structured MDS blocks: original tables stay tables in the editor and PDF. */
final class SwakelolaDocumentService
{
    public static function supports(string $code): bool { return str_starts_with($code, 'SW_'); }

    public static function catalog(): array
    {
        return json_decode(file_get_contents(__DIR__.'/../../resources/procurement/swakelola/catalog.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    /** Every paragraph, table, row and cell has its own database row. */
    public static function storeBlocks(DB $db, string $owner, int $id, array $blocks, ?int $parent=null): void
    {
        if (!in_array($owner,['master_bagian_id','dokumen_bagian_id'],true)) throw new InvalidArgumentException('Pemilik blok tidak valid');
        if ($parent===null) {
            self::validate($blocks);
            $db->query("DELETE FROM dokumen_pengadaan_paragraf_neo WHERE $owner=?",[$id]);
        }
        foreach ($blocks as $order=>$block) {
            $meta=$block;unset($meta['text'],$meta['rows'],$meta['blocks']);
            $node=(int)$db->insert('dokumen_pengadaan_paragraf_neo',[
                $owner=>$id,'parent_id'=>$parent,'jenis_blok'=>$block['type'],'urutan'=>$order,
                'isi'=>$block['text']??null,'format_data'=>json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
            if ($block['type']==='table') {
                foreach ($block['rows'] as $r=>$cells) {
                    $row=(int)$db->insert('dokumen_pengadaan_paragraf_neo',[$owner=>$id,'parent_id'=>$node,'jenis_blok'=>'row','urutan'=>$r]);
                    foreach ($cells as $c=>$cell) {
                        $cellId=(int)$db->insert('dokumen_pengadaan_paragraf_neo',[$owner=>$id,'parent_id'=>$row,'jenis_blok'=>'cell','urutan'=>$c,'format_data'=>json_encode(['colspan'=>$cell['colspan']??1])]);
                        self::storeBlocks($db,$owner,$id,$cell['blocks'],$cellId);
                    }
                }
            }
        }
    }

    public static function loadBlocks(DB $db, string $owner, int $id): ?array
    {
        if (!in_array($owner,['master_bagian_id','dokumen_bagian_id'],true)) throw new InvalidArgumentException('Pemilik blok tidak valid');
        $rows=$db->query("SELECT * FROM dokumen_pengadaan_paragraf_neo WHERE $owner=? ORDER BY urutan,id",[$id])->fetchAll();
        if (!$rows) return null;
        $children=[];foreach($rows as $row)$children[(int)$row['parent_id']][]=$row;
        $build=function(int $parent)use(&$build,$children):array {
            $blocks=[];
            foreach($children[$parent]??[] as $node){
                $block=json_decode($node['format_data']??'{}',true)?:[];$block['type']=$node['jenis_blok'];
                if($block['type']==='paragraph')$block['text']=$node['isi']??'';
                elseif($block['type']==='table'){
                    $block['rows']=[];
                    foreach($children[(int)$node['id']]??[] as $row){
                        $cells=[];
                        foreach($children[(int)$row['id']]??[] as $cell){
                            $meta=json_decode($cell['format_data']??'{}',true)?:[];
                            $cells[]=['colspan'=>(int)($meta['colspan']??1),'blocks'=>$build((int)$cell['id'])];
                        }
                        $block['rows'][]=$cells;
                    }
                }
                $blocks[]=$block;
            }
            return $blocks;
        };
        return $build(0);
    }

    public static function identity(array $c, array $user): array
    {
        return [
            'nama_opd'=>$user['nama_opd']??$user['nama_org']??$c['kd_opd']??'',
            'nama_paket'=>$c['uraian_kontrak']??'', 'tahun'=>(string)($c['tahun']??''),
            'nama_pelaksana'=>$c['nama_penyedia_resmi']??$c['nama_perusahaan']??$c['nama_penyedia']??'',
            'alamat_pelaksana'=>$c['alamat_penyedia']??$c['alamat']??'',
            'npwp'=>$c['npwp']??'', 'pimpinan_pelaksana'=>$c['direktur']??'',
            'nama_ppk'=>$c['nama_ppk_resmi']??$c['nama_ppk']??'',
            'nip_ppk'=>$c['nip_ppk_resmi']??$c['nip_ppk']??'',
            'nilai_kontrak'=>number_format((float)($c['nilai_kontrak']??0),2,',','.'),
            'sumber_dana'=>$c['sumber_dana']??'APBD',
        ];
    }

    public static function fill(array $blocks, array $vars): array
    {
        foreach ($blocks as &$block) {
            if (($block['type']??'')==='table') {
                foreach ($block['rows'] as &$row) foreach ($row as &$cell) $cell['blocks']=self::fill($cell['blocks'], $vars);
                unset($row,$cell);
            } else {
                $text=(string)($block['text']??'');
                foreach ($vars as $key=>$value) $text=str_replace('{{'.$key.'}}',(string)$value,$text);
                // Only replace unambiguous annotated blanks; other isian remain editable.
                $annotations=[
                    'nama (?:paket pengadaan(?: swakelola)?|Barang/Jasa)'=>'nama_paket',
                    'nama Pejabat Penandatangan Kontrak'=>'nama_ppk',
                    'nama satuan kerja Pejabat Penandatangan Kontrak'=>'nama_opd',
                    'nama Pelaksana\s+Swakelola'=>'pimpinan_pelaksana',
                    'diisi nama (?:ketua/pimpinan Pokmas|Pimpinan Ormas/PTS/Organisasi Profesi atau yang diberi kuasa)'=>'pimpinan_pelaksana',
                    '(?:diisi |ditulis )?nama Pokmas(?: Calon Pelaksana Swakelola)?'=>'nama_pelaksana',
                    'alamat sekretariat Pokmas calon Pelaksana Swakelola'=>'alamat_pelaksana',
                ];
                foreach ($annotations as $pattern=>$key) if (trim((string)($vars[$key]??''))!=='') {
                    $text=preg_replace_callback('~[_\.]{3,}\s*<\s*'.$pattern.'\s*>~iu',fn()=>(string)$vars[$key],$text);
                }
                $block['text']=$text;
            }
        }
        unset($block);
        return $blocks;
    }

    public static function validate(array $blocks, int $depth=0): void
    {
        if ($depth>12 || count($blocks)>20000) throw new InvalidArgumentException('Struktur dokumen terlalu besar');
        foreach ($blocks as $b) {
            if (!is_array($b)) throw new InvalidArgumentException('Blok dokumen tidak valid');
            if (($b['type']??'')==='paragraph') {
                if (!is_string($b['text']??null)) throw new InvalidArgumentException('Isi paragraf tidak valid');
            } elseif (($b['type']??'')==='table') {
                if (!is_array($b['rows']??null)) throw new InvalidArgumentException('Tabel tidak valid');
                foreach ($b['rows'] as $row) {
                    if (!is_array($row)) throw new InvalidArgumentException('Baris tabel tidak valid');
                    foreach ($row as $cell) {
                        if (!is_array($cell) || !is_array($cell['blocks']??null) || (int)($cell['colspan']??1)<1 || (int)($cell['colspan']??1)>100) throw new InvalidArgumentException('Sel tabel tidak valid');
                        self::validate($cell['blocks'], $depth+1);
                    }
                }
            } else throw new InvalidArgumentException('Jenis blok dokumen tidak valid');
        }
    }

    public static function html(array $blocks): string
    {
        self::validate($blocks);
        $html='';$escape=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        foreach ($blocks as $b) {
            if ($b['type']==='table') {
                $html.='<table border="1" cellpadding="4" width="100%">';
                foreach ($b['rows'] as $row) {
                    $html.='<tr>';$column=0;
                    foreach ($row as $cell) {
                        $span=(int)($cell['colspan']??1);$width='';
                        if(!empty($b['column_widths']))$width=' width="'.array_sum(array_slice($b['column_widths'],$column,$span)).'%"';
                        $html.='<td colspan="'.$span.'"'.$width.'>'.self::html($cell['blocks']).'</td>';$column+=$span;
                    }
                    $html.='</tr>';
                }
                $html.='</table><br>';
            } else {
                // The configured official letterhead replaces source kop placeholders.
                if (preg_match('/^\s*\[\s*Kop Surat[^\]]*\]\s*$/iu',$b['text'])) continue;
                $align=in_array($b['align']??'', ['left','right','center','justify'], true)?$b['align']:'left';
                $text=nl2br($escape($b['text']),false);
                if (in_array('italic',$b['style']??[],true)) $text='<i>'.$text.'</i>';
                if (in_array('underline',$b['style']??[],true)) $text='<u>'.$text.'</u>';
                $html.='<p align="'.$align.'">'.(!empty($b['bold'])?'<b>'.$text.'</b>':$text).'</p>';
            }
        }
        return $html;
    }

    public static function render(array $d, array $c, array $user, array $setup): string
    {
        $pdf=PageSetupService::createPdf($setup,$d['orientasi']??'P');
        PageSetupService::applyPdf($pdf,$setup,[18,15,18,18]);
        $pdf->SetTitle($d['judul']);$pdf->AddPage();
        OfficialLetterheadPdfService::draw($pdf,$user);
        $pdf->SetFont($setup['font']??'helvetica','',9);
        foreach ($d['sections'] as $section) {
            $blocks=json_decode($section['isi'],true,512,JSON_THROW_ON_ERROR);
            $pdf->writeHTML(self::html($blocks),true,false,true,false,'');
        }
        return $pdf->Output('','S');
    }
}
