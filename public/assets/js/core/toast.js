class Toast {
 static success(message){this.show({success:true,message});}
 static error(message){this.show({success:false,message});}
 static show({success=true,message=''}){
  if(!message)return;const container=document.getElementById('toastContainer');if(!container)return;const createdAt=Date.now();this.cleanup(true, createdAt);
  container.style.display='block';const toast=document.createElement('div');toast.className=`ui ${success?'success':'error'} message app-toast`;toast.setAttribute('role','status');toast.setAttribute('data-toast-created',String(createdAt));toast.innerHTML=`<i class="close icon" aria-label="Tutup"></i><div class="content"></div><div class="app-toast-progress"></div>`;toast.querySelector('.content').textContent=message;container.appendChild(toast);
  while(container.querySelectorAll('.app-toast').length>4)container.querySelector('.app-toast')?.remove();
  const remove=()=>{if(!toast.isConnected)return;toast.classList.add('app-toast-leave');setTimeout(()=>{toast.remove();if(!container.querySelector('.app-toast')){container.replaceChildren();container.style.display='none';}},180);};
  toast.querySelector('.close').addEventListener('click',remove,{once:true});toast._timer=setTimeout(remove,3200);
 }
 static cleanup(force=false,createdAt=Date.now()){const c=document.getElementById('toastContainer');if(!c)return;c.querySelectorAll(':scope > :not(.app-toast)').forEach(n=>n.remove());if(force)c.querySelectorAll('.app-toast').forEach(n=>{const born=Number(n.dataset.toastCreated||0);if(!born||createdAt-born>6000)n.remove();});if(!c.querySelector('.app-toast')){c.replaceChildren();c.style.display='none';}}
}
window.Toast=Toast;
