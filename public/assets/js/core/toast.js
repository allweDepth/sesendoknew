class Toast {
 static positions=['top right','top left','top center','bottom right','bottom left','bottom center'];
 static preferenceKey(){const u=window.app?.user||{};return 'sesendok-notification-position:'+String(u.username||u.id||'guest');}
 static position(){try{const value=localStorage.getItem(this.preferenceKey());return this.positions.includes(value)?value:'top right';}catch(_){return 'top right';}}
 static setPosition(value){if(!this.positions.includes(value))return false;try{localStorage.setItem(this.preferenceKey(),value);delete this.sessionPosition;}catch(_){this.sessionPosition=value;}this.place();return true;}
 static bindSettings(root=document){
  const form=root.querySelector('#form-notification-settings');
  if(!form)return;
  const select=form.querySelector('#notificationPosition');
  select.value=this.position();
  if(window.jQuery?.fn.dropdown)window.jQuery(select).dropdown('set selected',select.value);
  form.onsubmit=event=>{event.preventDefault();if(this.setPosition(select.value))this.success('Posisi notifikasi disimpan.');};
 }
 static place(){
  const c=document.getElementById('toastContainer');if(!c)return;
  const position=this.sessionPosition||this.position();
  let top=12,left=14;
  document.querySelectorAll('body > .ui.menu, #dynamicHeader').forEach(el=>{const r=el.getBoundingClientRect();if(r.height>0&&r.bottom>0)top=Math.max(top,r.bottom+12);});
  const sidebar=document.querySelector('.sidebarutama.visible');if(sidebar)left=Math.max(left,sidebar.getBoundingClientRect().right+12);
  left=Math.min(left,Math.max(14,innerWidth-c.offsetWidth-14));
  Object.assign(c.style,{top:position.startsWith('top')?top+'px':'auto',bottom:position.startsWith('bottom')?'16px':'auto',left:position.endsWith('left')?left+'px':position.endsWith('center')?'50%':'auto',right:position.endsWith('right')?'16px':'auto',transform:position.endsWith('center')?'translateX(-50%)':'none'});
  document.documentElement.style.setProperty('--app-toast-top',top+'px');
  if(window.jQuery?.fn.toast?.settings)window.jQuery.fn.toast.settings.position=position;
 }
 static success(message){this.show({success:true,message});}
 static error(message){this.show({success:false,message});}
 static show({success=true,message=''}){
  if(!message)return;const container=document.getElementById('toastContainer');if(!container)return;const createdAt=Date.now();this.cleanup(true, createdAt);
  container.style.display='block';this.place();const toast=document.createElement('div');toast.className=`ui ${success?'success':'error'} message app-toast`;toast.setAttribute('role','status');toast.setAttribute('data-toast-created',String(createdAt));toast.innerHTML=`<i class="close icon" aria-label="Tutup"></i><div class="content"></div><div class="app-toast-progress"></div>`;toast.querySelector('.content').textContent=message;container.appendChild(toast);
  while(container.querySelectorAll('.app-toast').length>4)container.querySelector('.app-toast')?.remove();
  const remove=()=>{if(!toast.isConnected)return;toast.classList.add('app-toast-leave');setTimeout(()=>{toast.remove();if(!container.querySelector('.app-toast')){container.replaceChildren();container.style.display='none';}},180);};
  toast.querySelector('.close').addEventListener('click',remove,{once:true});toast._timer=setTimeout(remove,3200);
 }
 static cleanup(force=false,createdAt=Date.now()){const c=document.getElementById('toastContainer');if(!c)return;c.querySelectorAll(':scope > :not(.app-toast)').forEach(n=>n.remove());if(force)c.querySelectorAll('.app-toast').forEach(n=>{const born=Number(n.dataset.toastCreated||0);if(!born||createdAt-born>6000)n.remove();});if(!c.querySelector('.app-toast')){c.replaceChildren();c.style.display='none';}}
}
window.Toast=Toast;

window.addEventListener('resize',()=>Toast.place());
window.addEventListener('storage',e=>{if(e.key===Toast.preferenceKey())Toast.place();});
document.addEventListener('DOMContentLoaded',()=>Toast.place());
