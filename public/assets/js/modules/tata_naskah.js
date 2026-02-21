$(document).ready(function(){
$('.kelompok-card').on('click', function () {
    let id = $(this).data('id');

    $.post('/tata_naskah/load_jenis', {kelompok_id: id}, function (res) {
        let data = JSON.parse(res);

        let html = '';

        data.forEach(j => {
            html += `
                <div class="item">
                    <a href="#" class="jenis-item" data-id="${j.id}">
                        <i class="file outline icon"></i>
                        <div class="content">
                            <div class="header">${j.nama}</div>
                        </div>
                    </a>
                </div>
            `;
        });

        $('#jenis-list').html(html);
        $('#jenis-container').removeClass('hidden');
        $('#form-container').addClass('hidden');
    });
});

$(document).on('click', '.jenis-item', function (e) {
    e.preventDefault();

    let id = $(this).data('id');

    $.post('/tata_naskah/load_form', {jenis_id: id}, function (schema) {
        if (!schema) return;

        let formSchema = JSON.parse(schema);
        let html = buildForm(formSchema);

        $('#form-container').html(html).removeClass('hidden');
    });
});

function buildForm(schema) {
    let html = '<form class="ui form">';
    schema.forEach(field => {
        html += `
            <div class="field">
                <label>${field.label}</label>
                <input type="text" name="${field.name}">
            </div>
        `;
    });

    html += '<button class="ui primary button">Simpan Draft</button>';
    html += '</form>';

    return html;
}
});