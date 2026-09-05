<?php

require_once __DIR__.'/../../vendor/tecnickcom/tcpdf/tcpdf.php';

/** TCPDF dengan header/footer tiga kolom yang dikendalikan Page Setup global. */
final class PageSetupPdf extends TCPDF
{
    private array $pageSetup=[];

    public function configurePageSetup(array $settings):void{$this->pageSetup=$settings;}

    public function Header():void
    {
        if(empty($this->pageSetup['header_enabled']))return;
        $this->renderSection('header');
    }

    public function Footer():void
    {
        if(empty($this->pageSetup['footer_enabled']))return;
        $this->renderSection('footer');
    }

    private function renderSection(string $kind):void
    {
        $config=$this->pageSetup[$kind]??[];$columns=$config['columns']??[];if(!$columns)return;
        $height=(float)($this->pageSetup[$kind.'_height']??10);$edge=(float)($this->pageSetup['margin_'.$kind]??5);
        $margins=$this->getMargins();$left=(float)($margins['left']??10);$right=(float)($margins['right']??10);$available=$this->getPageWidth()-$left-$right;
        $y=$kind==='header'?$edge:$this->getPageHeight()-$edge-$height;
        $style=(!empty($config['bold'])?'B':'').(!empty($config['italic'])?'I':'').(!empty($config['underline'])?'U':'');
        $this->SetFont((string)($config['font']??'helvetica'),$style,(float)($config['size']??8));$this->SetTextColor(...$this->color((string)($config['color']??'#222222')));
        $sum=array_sum(array_map(fn($column)=>(float)($column['width']??0),$columns))?:100;$x=$left;
        foreach(array_slice($columns,0,3) as $column){$width=$available*((float)($column['width']??0)/$sum);$text=$this->tokens((string)($column['text']??''));$align=in_array(($column['align']??'L'),['L','C','R'],true)?$column['align']:'L';$this->MultiCell($width,$height,$text,0,$align,false,0,$x,$y,true,0,false,true,$height,'M');$x+=$width;}
        if(!empty($config['divider'])){$lineY=$kind==='header'?$y+$height:$y;$this->SetDrawColor(...$this->color((string)($config['color']??'#222222')));$this->Line($left,$lineY,$this->getPageWidth()-$right,$lineY);}
        $this->SetTextColor(0,0,0);$this->SetDrawColor(0,0,0);
    }

    private function tokens(string $text):string
    {
        $user=$_SESSION['user']??[];return strtr($text,['{page}'=>$this->getAliasNumPage(),'{pages}'=>$this->getAliasNbPages(),'{date}'=>date('d-m-Y'),'{year}'=>(string)($user['tahun']??date('Y')),'{opd}'=>(string)($user['nama_opd']??$user['nama_org']??''),'{wilayah}'=>(string)($user['nama_pemda']??$user['kd_wilayah']??'')]);
    }

    private function color(string $hex):array{$hex=ltrim($hex,'#');return preg_match('/^[0-9a-f]{6}$/i',$hex)?[hexdec(substr($hex,0,2)),hexdec(substr($hex,2,2)),hexdec(substr($hex,4,2))]:[34,34,34];}
}
