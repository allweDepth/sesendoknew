// Fomantic notifications and schedule badges for server-rendered/SPA dashboards.
(() => {
    const mounted = new WeakMap();
    const escape = value => $('<div>').text(value ?? '').html();
    function render(root, data) {
        const list = root.querySelector('[data-dashboard-schedules]');
        if (list && data.schedules) {
            const html = Object.values(data.schedules).map(status => `<div class="item"><div class="right floated content"><span class="ui mini ${status.enabled ? 'green' : 'grey'} label">${status.enabled ? 'Enabled' : 'Disabled'}</span></div><div class="content"><div class="header">${escape(status.label)}</div><div class="description">${escape(status.status)}</div></div></div>`).join('');
            if (list.innerHTML !== html) list.innerHTML=html;
        }
        const notification = root.querySelector('[data-messenger-notification]');
        if (notification && Number.isInteger(data.unread)) {
            const text=data.unread>0?`${data.unread} pesan belum dibaca.`:'Belum ada pesan baru.';
            if(notification.textContent!==text)notification.textContent=text;
            $(notification).closest('.message').toggleClass('info', data.unread === 0).toggleClass('warning', data.unread > 0);
        }
        const sticky = $(root).find('.dashboard-messenger-notice');
        if (sticky.length) sticky.sticky('refresh');
    }
    function refresh(root) {
        const state=mounted.get(root);
        if (!root.isConnected || document.hidden || !state || state.busy || Date.now()-state.refreshedAt<55000) return;
        state.busy=true;
        window.Ajax.request({url:'/dashboard/status',method:'GET',silent:true,timeout:10000,data:{mod:root.dataset.dashboardModule},success:res=>{
            if (res?.success && root.isConnected) {
                state.refreshedAt=Date.now();
                const signature=JSON.stringify(res.data);
                if(state.signature!==signature){state.signature=signature;render(root,res.data);}
            }
        },complete:()=>{state.busy=false;}});
    }
    function mount() {
        document.querySelectorAll('.module-dashboard[data-dashboard-module]').forEach(root => {
            if (mounted.has(root) || !root.querySelector('[data-dashboard-schedules], [data-messenger-notification]')) return;
            mounted.set(root,{busy:false,refreshedAt:Date.now(),signature:null});
            const sticky = $(root).find('.dashboard-messenger-notice');
            if (sticky.length) sticky.sticky({context:root,scrollContext:$('.content-scroll'),offset:8,bottomOffset:8,observeChanges:false});
        });
    }
    $(function() {
        mount();
        new MutationObserver(records=>{
            if(records.some(record=>Array.from(record.addedNodes).some(node=>node.nodeType===1&&(node.matches('.module-dashboard')||node.querySelector('.module-dashboard')))))mount();
        }).observe(document.getElementById('main-content'),{childList:true,subtree:true});
        setInterval(() => document.querySelectorAll('.module-dashboard[data-dashboard-module]').forEach(refresh), 60000);
        window.addEventListener('focus', () => document.querySelectorAll('.module-dashboard[data-dashboard-module]').forEach(refresh));
    });
})();
