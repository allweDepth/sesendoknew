/**
 * ============================================================
 * WALLCHAT MODULE
 * ============================================================
 * Modul khusus tanpa table
 */

class WallchatModule {

    constructor() {

        this.state = window.app.state;
        this.ajax = window.app.ajax;

        this.mainContainer = "#main-content";
    }

    init() {

        this.renderLayout();
        this.bindEvents();
    }

    renderLayout() {

        const html = `
            <div class="ui segment">
                <h3 class="ui header">Wallchat</h3>
                <div id="chat-box"></div>
                <form id="chat-form" class="ui form">
                    <div class="field">
                        <input type="text" name="message" placeholder="Ketik pesan...">
                    </div>
                    <button class="ui primary button">Kirim</button>
                </form>
            </div>
        `;

        $(this.mainContainer).html(html);
    }

    bindEvents() {

        $(document).on("submit", "#chat-form", (e) => {

            e.preventDefault();

            const data = $("#chat-form").serialize();

            this.ajax.request({
                data: data,
                success: () => {
                    $("#chat-form")[0].reset();
                }
            });

        });

    }

    destroy() {

        $(document).off("submit", "#chat-form");

        $(this.mainContainer).empty();
    }

}