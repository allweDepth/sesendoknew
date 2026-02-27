class UIExtensions {

    static currency(selector) {

        $(document).on('input', selector, function() {

            let value = this.value.replace(/\D/g, '');

            this.value = new Intl.NumberFormat('id-ID').format(value);

        });

    }

}

window.UIExtensions = UIExtensions;