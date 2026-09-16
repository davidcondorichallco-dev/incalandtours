import './bootstrap';

const icons = {
  grid:'<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
  users:'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
  route:'<circle cx="6" cy="19" r="3"/><circle cx="18" cy="5" r="3"/><path d="M9 19h3a6 6 0 0 0 6-6v-5M6 16V9a4 4 0 0 1 4-4h5"/>',
  map:'<polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21 3 6"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/>',
  backpack:'<path d="M6 8V6a6 6 0 0 1 12 0v2"/><rect x="4" y="7" width="16" height="14" rx="3"/><path d="M8 12h8M8 16h8M4 13H2v5h2M20 13h2v5h-2"/>',
  bed:'<path d="M3 4v16M21 20v-8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8M3 16h18M7 10V7h5a2 2 0 0 1 2 2v1"/>',
  store:'<path d="M3 9l2-5h14l2 5M5 13v7h14v-7M9 20v-6h6v6"/><path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/>',
  badge:'<circle cx="12" cy="8" r="5"/><path d="M8.5 12 7 22l5-3 5 3-1.5-10"/>',
  bell:'<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
  globe:'<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
  search:'<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
  bike:'<circle cx="6" cy="17" r="4"/><circle cx="18" cy="17" r="4"/><path d="m6 17 4-8h4l4 8M9 11h7M10 9 8 6h3M14 9l2-3"/>',
  helmet:'<path d="M4 14v-3a8 8 0 0 1 16 0v3H4Z"/><path d="M4 14h12v4H9a5 5 0 0 1-5-4ZM12 3v7"/>',
  qr:'<rect x="3" y="3" width="6" height="6"/><rect x="15" y="3" width="6" height="6"/><rect x="3" y="15" width="6" height="6"/><path d="M15 15h2v2h-2zM19 15h2v6h-6v-2M11 3v4M11 11h4M7 11v2M11 17v4"/>',
  lock:'<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>'
  ,image:'<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/>'
  ,user:'<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>'
  ,book:'<path d="M4 4h6a3 3 0 0 1 3 3v13a3 3 0 0 0-3-3H4z"/><path d="M20 4h-6a3 3 0 0 0-3 3v13a3 3 0 0 1 3-3h6z"/>'
};
document.querySelectorAll('[data-icon]').forEach(el=>{ el.innerHTML=`<svg viewBox="0 0 24 24" aria-hidden="true">${icons[el.dataset.icon]||icons.grid}</svg>`; });

const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
const baseUrl=(document.querySelector('meta[name="app-base-url"]')?.content||'').replace(/\/$/,'');
const appUrl=path=>`${baseUrl}${path.startsWith('/')?path:`/${path}`}`;
const escapeHtml=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
const showToast=(message,error=false)=>{const t=document.getElementById('toast');if(!t)return;t.querySelector('span').textContent=message;t.querySelector('i').textContent=error?'!':'✓';t.querySelector('i').style.background=error?'#df0b2f':'#137a51';t.classList.add('show');setTimeout(()=>t.classList.remove('show'),3300)};
const openModal=id=>{document.getElementById(id)?.classList.add('open');document.body.style.overflow='hidden'};
const closeModals=()=>{document.querySelectorAll('.modal.open').forEach(x=>x.classList.remove('open'));document.body.style.overflow=''};
document.querySelectorAll('[data-close-modal]').forEach(x=>x.addEventListener('click',closeModals));
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModals()});

// Show the server-enforced login lock without relying on the browser for security.
const loginForm=document.querySelector('[data-login-form]');
if(loginForm){
  const button=loginForm.querySelector('[data-login-button]');
  let remaining=Number(loginForm.dataset.retryAfter||0);
  const renderLock=()=>{
    if(remaining<=0){button.disabled=false;button.innerHTML='Ingresar al sistema <span>→</span>';return}
    button.disabled=true;button.textContent=`Espera ${remaining} segundo${remaining===1?'':'s'}`;
    remaining--;setTimeout(renderLock,1000);
  };
  if(remaining>0)renderLock();
}

const api=async(url,method,data)=>{
  const response=await fetch(url,{method,headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify(data)});
  const json=await response.json().catch(()=>({message:'No se pudo procesar la respuesta.'}));
  if(!response.ok) throw new Error(Object.values(json.errors||{}).flat()[0]||json.message||'Ocurrió un error.');
  return json;
};

// Dashboard navigation
const staffFirstName=window.Incaland?.staff?.full_name?.split(' ')[0]||'Equipo';
const titles={dashboard:['CENTRO DE OPERACIONES',`Buenos días, ${staffFirstName} 👋`],reception:['ATENCIÓN AL VIAJERO','Recepción'],departures:['PLANIFICACIÓN','Salidas programadas'],packages:['CATÁLOGO','Paquetes turísticos'],carousel:['PORTADA DEL SITIO','Carrusel del home'],equipment:['INVENTARIO','Equipamiento'],lodgings:['HOSPEDAJE','Hospedajes'],branches:['ADMINISTRACIÓN','Sucursales'],team:['ADMINISTRACIÓN','Personal'],profile:['CUENTA DE ADMINISTRACIÓN','Mi perfil']};
function switchView(name){
  const defaultView=window.Incaland?.defaultView||'dashboard';
  if(!document.getElementById(`view-${name}`))name=defaultView;
  document.querySelectorAll('.view').forEach(v=>v.classList.toggle('active',v.id===`view-${name}`));
  document.querySelectorAll('[data-view]').forEach(a=>a.classList.toggle('active',a.dataset.view===name));
  const title=titles[name];if(title){document.getElementById('sectionEyebrow').textContent=title[0];document.getElementById('sectionTitle').textContent=title[1]}
  document.getElementById('sidebar')?.classList.remove('open');window.scrollTo({top:0,behavior:'smooth'});
  return name;
}
const navigateDashboard=name=>{
  const activeView=switchView(name);
  history.replaceState(null,'',`#${activeView}`);
};
document.querySelectorAll('[data-view]').forEach(a=>a.addEventListener('click',e=>{e.preventDefault();navigateDashboard(a.dataset.view)}));
document.querySelectorAll('[data-jump]').forEach(a=>a.addEventListener('click',e=>{e.preventDefault();navigateDashboard(a.dataset.jump)}));
if(document.querySelector('.app-shell')){
  navigateDashboard(location.hash.slice(1)||window.Incaland.defaultView);
  window.addEventListener('hashchange',()=>switchView(location.hash.slice(1)));
  window.addEventListener('pageshow',event=>{if(event.persisted)location.reload()});
}
document.getElementById('menuBtn')?.addEventListener('click',()=>document.getElementById('sidebar').classList.toggle('open'));

// End the session without leaving the private dashboard in the current history entry.
document.querySelector('[data-logout-form]')?.addEventListener('submit',async event=>{
  event.preventDefault();
  const form=event.currentTarget,button=form.querySelector('button');
  button.disabled=true;
  try{
    const response=await fetch(form.action,{method:'POST',headers:{'Accept':'text/html'},body:new FormData(form)});
    if(!response.ok)throw new Error('No se pudo cerrar la sesión.');
    location.replace(appUrl('/ingresar'));
  }catch(error){
    button.disabled=false;
    form.submit();
  }
});

document.getElementById('profileForm')?.addEventListener('submit',async event=>{
  event.preventDefault();
  const form=event.currentTarget,button=form.querySelector('[data-profile-submit]');
  const data=Object.fromEntries(new FormData(form));
  button.disabled=true;const old=button.textContent;button.textContent='Guardando...';
  try{
    const result=await api(appUrl('/perfil'),'PUT',data);
    showToast(result.message);
    form.elements.current_password.value='';form.elements.password.value='';form.elements.password_confirmation.value='';
    setTimeout(()=>location.reload(),900);
  }catch(error){showToast(error.message,true);button.disabled=false;button.textContent=old}
});

// Search and departure filtering
document.querySelectorAll('[data-filter]').forEach(input=>input.addEventListener('input',()=>{const q=input.value.toLowerCase();document.querySelectorAll(`#${input.dataset.filter} tbody tr`).forEach(r=>r.style.display=r.textContent.toLowerCase().includes(q)?'':'none')}));
const applyDepartureFilters=()=>{const date=document.getElementById('departureDate')?.value||'',pack=document.getElementById('departurePackage')?.value||'',q=(document.getElementById('departureSearch')?.value||'').toLowerCase();document.querySelectorAll('.departure-card').forEach(card=>{card.style.display=(!date||card.dataset.date===date)&&(!pack||card.dataset.package===pack)&&(!q||card.textContent.toLowerCase().includes(q))?'grid':'none'})};
['departureDate','departurePackage','departureSearch'].forEach(id=>document.getElementById(id)?.addEventListener('input',applyDepartureFilters));
document.getElementById('clearFilters')?.addEventListener('click',()=>{['departureDate','departurePackage','departureSearch'].forEach(id=>document.getElementById(id).value='');applyDepartureFilters()});
document.querySelectorAll('[data-reception-status]').forEach(btn=>btn.addEventListener('click',()=>{document.querySelectorAll('[data-reception-status]').forEach(x=>x.classList.remove('active'));btn.classList.add('active');const filter=btn.dataset.receptionStatus;document.querySelectorAll('#receptionTable tbody tr').forEach(row=>{const pending=row.dataset.reservationStatus!=='confirmed';row.style.display=filter==='all'||(filter==='pending'&&pending)||(filter==='confirmed'&&!pending)?'':'none'})}));
document.querySelector('[data-reception-status].active')?.click();
document.querySelectorAll('[data-public-filter]').forEach(btn=>btn.addEventListener('click',()=>{document.querySelectorAll('[data-public-filter]').forEach(x=>x.classList.remove('active'));btn.classList.add('active');document.querySelectorAll('.public-package').forEach(card=>card.style.display=!btn.dataset.publicFilter||card.dataset.category===btn.dataset.publicFilter?'block':'none')}));

// Automatic home carousel with manual controls.
document.querySelectorAll('[data-home-carousel]').forEach(carousel=>{
  const slides=[...carousel.querySelectorAll('[data-carousel-slide]')],dots=[...carousel.querySelectorAll('[data-carousel-dot]')];
  if(!slides.length)return;
  let current=0,timer;
  const show=index=>{
    current=(index+slides.length)%slides.length;
    slides.forEach((slide,i)=>{const active=i===current;slide.classList.toggle('active',active);slide.setAttribute('aria-hidden',String(!active))});
    dots.forEach((dot,i)=>{const active=i===current;dot.classList.toggle('active',active);dot.setAttribute('aria-current',String(active))});
  };
  const play=()=>{if(slides.length>1&&!window.matchMedia('(prefers-reduced-motion: reduce)').matches)timer=setInterval(()=>show(current+1),6500)};
  const restart=()=>{clearInterval(timer);play()};
  carousel.querySelector('[data-carousel-previous]')?.addEventListener('click',()=>{show(current-1);restart()});
  carousel.querySelector('[data-carousel-next]')?.addEventListener('click',()=>{show(current+1);restart()});
  dots.forEach(dot=>dot.addEventListener('click',()=>{show(Number(dot.dataset.carouselDot));restart()}));
  carousel.addEventListener('mouseenter',()=>clearInterval(timer));carousel.addEventListener('mouseleave',play);
  carousel.addEventListener('keydown',event=>{if(event.key==='ArrowLeft'){show(current-1);restart()}if(event.key==='ArrowRight'){show(current+1);restart()}});
  if(slides.length===1)carousel.classList.add('single-slide');
  play();
});

// Reception workflow
document.querySelectorAll('[data-open-reservation]').forEach(btn=>btn.addEventListener('click',()=>{
  const d=window.Incaland,res=d.reservations.find(r=>Number(r.id)===Number(btn.dataset.openReservation));if(!res)return;
  const modal=document.getElementById('reservationContent');
  modal.innerHTML=`<p class="eyebrow">FICHA DEL VIAJERO</p><h2>${escapeHtml(res.full_name)}</h2><p class="modal-lead">${escapeHtml(res.nationality)} · Pasaporte ${escapeHtml(res.passport_number)} · ${escapeHtml(res.whatsapp)}</p>
  <div class="next-meta"><span><small>ALIMENTACIÓN</small><strong>${escapeHtml(res.food_notes||'Sin observaciones')}</strong></span><span><small>TALLA / ESTATURA</small><strong>${escapeHtml(res.apparel_size||'—')} · ${escapeHtml(res.height_cm||'—')} cm</strong></span></div>
  <form id="reservationForm"><input type="hidden" name="id" value="${Number(res.id)}"><div class="form-grid">
  <label>Paquete turístico<select name="tour_package_id" required><option value="">Seleccionar paquete</option>${d.packages.map(p=>`<option value="${Number(p.id)}" ${Number(p.id)===Number(res.tour_package_id)?'selected':''}>${escapeHtml(p.name)}</option>`).join('')}</select></label>
  <label>Fecha de salida<input type="date" name="tour_date" value="${escapeHtml(res.tour_date||'')}" min="${new Date().toISOString().slice(0,10)}" required></label>
  <div class="span-2"><p class="eyebrow">ASIGNAR EQUIPAMIENTO</p></div><div class="equipment-options">${d.equipment.map(e=>`<label><input type="checkbox" name="equipment_ids" value="${Number(e.id)}"><span>${escapeHtml(e.name)}<small class="block">${Number(e.available_stock)} disponibles · ${escapeHtml(e.size||'Talla única')}</small></span></label>`).join('')}</div>
  <label class="span-2">Notas de recepción<textarea name="notes" placeholder="Necesidades especiales o indicaciones"></textarea></label></div><button class="primary full" type="submit">Confirmar y enviar a salidas →</button></form>`;
  openModal('reservationModal');
  document.getElementById('reservationForm').addEventListener('submit',async e=>{e.preventDefault();const f=new FormData(e.target);try{const result=await api(appUrl(`/reservas/${f.get('id')}/completar`),'PUT',{tour_package_id:f.get('tour_package_id'),tour_date:f.get('tour_date'),equipment_ids:f.getAll('equipment_ids'),notes:f.get('notes')});showToast(result.message);closeModals();setTimeout(()=>location.reload(),700)}catch(err){showToast(err.message,true)}});
}));

// Close departure
document.querySelectorAll('[data-close-departure]').forEach(btn=>btn.addEventListener('click',()=>{document.querySelector('#departureForm [name="departure_id"]').value=btn.dataset.closeDeparture;openModal('departureModal')}));
document.querySelectorAll('[data-departure-summary]').forEach(btn=>btn.addEventListener('click',()=>showToast(btn.dataset.departureSummary)));
document.getElementById('departureForm')?.addEventListener('submit',async e=>{e.preventDefault();const f=Object.fromEntries(new FormData(e.target));const id=f.departure_id;delete f.departure_id;try{const result=await api(appUrl(`/salidas/${id}/cerrar`),'PUT',f);showToast(result.message);closeModals();setTimeout(()=>location.reload(),700)}catch(err){showToast(err.message,true)}});

// Editable catalog forms for admin and reception
const field=(name,label,type='text',options='')=>`<label>${label}${type==='select'?`<select name="${name}" required>${options}</select>`:`<input name="${name}" type="${type}" ${type!=='file'?'required':''}>`}</label>`;
const resourceConfig={
  branches:{title:'Nueva sucursal',fields:()=>field('name','Nombre')+field('city','Ciudad')+field('address','Dirección')+field('phone','Teléfono')},
  categories:{title:'Nueva categoría',fields:()=>field('name','Nombre')+field('color','Color','color')},
  packages:{title:'Nuevo paquete turístico',fields:()=>field('name','Nombre')+field('tour_category_id','Categoría','select',`<option value="">Seleccionar</option>${[...new Map(window.Incaland.packages.map(p=>[p.tour_category_id,{id:p.tour_category_id,name:p.category_name}])).values()].map(c=>`<option value="${Number(c.id)}">${escapeHtml(c.name)}</option>`).join('')}`)+field('location','Destino')+field('duration_days','Duración (días)','number')+field('price','Precio (Bs)','number')+'<label class="span-2">Descripción<textarea name="description"></textarea></label>'+field('image','Imagen','file')},
  equipment:{title:'Registrar equipamiento',fields:()=>field('name','Nombre')+field('category','Tipo')+field('size','Tallas')+field('stock','Cantidad','number')+field('condition','Estado','select','<option>Excelente</option><option>Bueno</option><option>Mantenimiento</option>')+field('image','Imagen','file')},
  lodgings:{title:'Nuevo hospedaje',fields:()=>field('name','Nombre')+field('city','Ciudad')+field('address','Dirección')+field('rooms','Habitaciones','number')+field('available_rooms','Disponibles','number')+field('image','Imagen','file')},
  employees:{title:'Nuevo empleado',fields:()=>field('full_name','Nombre completo')+field('username','Nombre de usuario')+field('email','Correo','email')+field('phone','Teléfono')+field('password','Contraseña temporal','password')+field('role','Rol','select','<option value="receptionist">Recepcionista</option><option value="admin">Administrador</option>')+field('branch_id','Sucursal','select',`<option value="">Seleccionar</option>${window.Incaland.branches.map(b=>`<option value="${Number(b.id)}">${escapeHtml(b.name)}</option>`).join('')}`)}
  ,slides:{title:'Nueva diapositiva',fields:()=>field('title','Título')+'<label class="span-2">Subtítulo<textarea name="subtitle" maxlength="280" placeholder="Texto breve que acompaña al título"></textarea></label>'+field('display_order','Orden','number')+field('active','Visibilidad','select','<option value="1">Visible</option><option value="0">Oculta</option>')+'<label class="span-2">Imagen <small>JPG, PNG o WebP · máximo 8 MB</small><input name="image" type="file" accept="image/jpeg,image/png,image/webp"></label>'}
};
const collectionFor=type=>({branches:'branches',categories:'categories',packages:'packages',equipment:'equipment',lodgings:'lodgings',employees:'employees',slides:'slides'}[type]);
const showResourceForm=(type,record=null)=>{const c=resourceConfig[type],form=document.getElementById('resourceForm');form.reset();document.getElementById('resourceTitle').textContent=record?c.title.replace(/Nuev[oa]|Registrar/,'Editar'):c.title;form.querySelector('[name="resource_type"]').value=type;form.querySelector('[name="resource_id"]').value=record?.id||'';document.getElementById('resourceFields').innerHTML=c.fields();if(type==='slides'&&!record)form.elements.image.required=true;if(record){Object.entries(record).forEach(([key,value])=>{const input=form.elements[key];if(input&&input.type!=='file'&&key!=='password')input.value=value??''});const password=form.elements.password;if(password)password.required=false}openModal('resourceModal')};
document.querySelectorAll('[data-new]').forEach(btn=>btn.addEventListener('click',()=>showResourceForm(btn.dataset.new)));
document.querySelectorAll('[data-edit]').forEach(btn=>btn.addEventListener('click',()=>{const type=btn.dataset.edit,record=window.Incaland[collectionFor(type)]?.find(x=>Number(x.id)===Number(btn.dataset.id));if(record)showResourceForm(type,record)}));
document.getElementById('resourceForm')?.addEventListener('submit',async e=>{e.preventDefault();const fd=new FormData(e.target),type=fd.get('resource_type'),id=fd.get('resource_id');fd.delete('resource_type');fd.delete('resource_id');if(id)fd.append('_method','PUT');try{const response=await fetch(appUrl(id?`/catalogo/${type}/${id}`:`/catalogo/${type}`),{method:'POST',headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf},body:fd});const json=await response.json();if(!response.ok)throw new Error(Object.values(json.errors||{}).flat()[0]||json.message);showToast(json.message);closeModals();setTimeout(()=>location.reload(),700)}catch(err){showToast(err.message,true)}});
document.querySelectorAll('[data-delete-slide]').forEach(button=>button.addEventListener('click',async()=>{
  if(!confirm(`¿Eliminar “${button.dataset.slideTitle}”? Esta acción no se puede deshacer.`))return;
  button.disabled=true;
  try{const result=await api(appUrl(`/carrusel/${button.dataset.deleteSlide}`),'DELETE',{});showToast(result.message);setTimeout(()=>location.reload(),650)}catch(error){button.disabled=false;showToast(error.message,true)}
}));

// Branch QR
document.querySelectorAll('[data-qr]').forEach(btn=>btn.addEventListener('click',()=>{document.getElementById('qrTitle').textContent=btn.dataset.branch;document.getElementById('qrLink').href=btn.dataset.qr;document.getElementById('qrImage').src=`https://api.qrserver.com/v1/create-qr-code/?size=360x360&margin=10&data=${encodeURIComponent(btn.dataset.qr)}`;openModal('qrModal')}));

// Public traveler form and translations
const translations={
  en:{hero:'Your adventure starts here.',hero_sub:'Tell us a little about yourself and we will take care of the rest.',title:'Traveler details',intro:'We need this information to prepare a safe, personalized experience.',full_name:'Full name',nationality:'Nationality',passport:'Passport number',food:'Food preferences',size:'General apparel size',height:'Height (cm)',hotel:'Hotel / lodging',package:'Tour package',date:'Departure date',consent:'I confirm that the information provided is correct.',submit:'Send my details',privacy:'Your information is protected and will only be used to coordinate your trip.',success:'We received your details',back:'Back to home'},
  fr:{hero:'Votre aventure commence ici.',hero_sub:'Parlez-nous un peu de vous, nous nous occupons du reste.',title:'Informations du voyageur',intro:'Ces informations nous aident à préparer une expérience sûre et personnalisée.',full_name:'Nom complet',nationality:'Nationalité',passport:'Numéro de passeport',food:'Préférences alimentaires',size:'Taille générale',height:'Taille (cm)',hotel:'Hôtel / hébergement',package:'Forfait touristique',date:'Date de départ',consent:'Je confirme que les informations sont correctes.',submit:'Envoyer mes informations',privacy:'Vos données sont protégées et utilisées uniquement pour organiser le voyage.',success:'Nous avons reçu vos informations',back:"Retour à l'accueil"},
  pt:{hero:'Sua aventura começa aqui.',hero_sub:'Conte-nos um pouco sobre você e cuidaremos do resto.',title:'Dados do viajante',intro:'Precisamos destas informações para preparar uma experiência segura e personalizada.',full_name:'Nome completo',nationality:'Nacionalidade',passport:'Número do passaporte',food:'Alimentação',size:'Tamanho de roupa',height:'Altura (cm)',hotel:'Hotel / hospedagem',package:'Pacote turístico',date:'Data de saída',consent:'Confirmo que as informações estão corretas.',submit:'Enviar meus dados',privacy:'Seus dados estão protegidos e serão usados apenas para coordenar sua viagem.',success:'Recebemos seus dados',back:'Voltar ao início'}
};
document.getElementById('languageSelect')?.addEventListener('change',e=>{const lang=e.target.value,dict=translations[lang]||{};document.documentElement.lang=lang;document.getElementById('preferredLanguage').value=lang;document.querySelectorAll('[data-t]').forEach(el=>{if(dict[el.dataset.t])el.innerHTML=dict[el.dataset.t]})});
const updateTravelerIndexes=()=>{document.querySelectorAll('.traveler-member').forEach((member,index)=>{member.dataset.index=index;member.querySelector('.member-head strong').textContent=index===0?'Viajero principal':`Viajero ${index+1}`;member.querySelectorAll('[data-field]').forEach(input=>input.name=`travelers[${index}][${input.dataset.field}]`)});const total=document.querySelectorAll('.traveler-member').length,count=document.getElementById('travelerCount');if(count)count.textContent=`${total} ${total===1?'viajero':'viajeros'}`};
document.getElementById('addTraveler')?.addEventListener('click',()=>{const container=document.getElementById('travelersContainer'),clone=container.querySelector('.traveler-member').cloneNode(true);clone.querySelectorAll('input').forEach(input=>input.value='');clone.querySelectorAll('select').forEach(select=>select.selectedIndex=0);clone.querySelector('.member-head').insertAdjacentHTML('beforeend','<button type="button" data-remove-traveler>Quitar</button>');container.appendChild(clone);updateTravelerIndexes();clone.scrollIntoView({behavior:'smooth',block:'center'})});
document.getElementById('travelersContainer')?.addEventListener('click',e=>{if(e.target.matches('[data-remove-traveler]')){e.target.closest('.traveler-member').remove();updateTravelerIndexes()}});
document.getElementById('touristForm')?.addEventListener('submit',async e=>{
  e.preventDefault();
  const btn=e.target.querySelector('button[type="submit"]'),old=btn.innerHTML;
  btn.disabled=true;btn.innerHTML='<span>Enviando...</span>';
  try{
    const fd=new FormData(e.target);
    let data=Object.fromEntries(fd);
    if(fd.get('source')==='online'){
      data={
        source:'online',preferred_language:fd.get('preferred_language'),contact_name:fd.get('contact_name'),
        contact_email:fd.get('contact_email'),contact_whatsapp:fd.get('contact_whatsapp'),
        tour_package_id:fd.get('tour_package_id'),tour_date:fd.get('tour_date'),
        travelers:[...document.querySelectorAll('.traveler-member')].map(member=>
          Object.fromEntries([...member.querySelectorAll('[data-field]')].map(input=>[input.dataset.field,input.value]))
        )
      };
    }
    const result=await api(appUrl('/reservas'),'POST',data);
    document.querySelector('.form-intro').classList.add('hidden');e.target.classList.add('hidden');
    document.getElementById('successMessage').textContent=result.message;document.getElementById('successScreen').classList.add('show');
  }catch(err){showToast(err.message,true);btn.disabled=false;btn.innerHTML=old}
});
