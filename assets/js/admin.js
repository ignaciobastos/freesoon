(() => {
  const root = document.querySelector('#obra-studio'); if (!root) return;
  const tr=(es,en)=>OBRA_DATA.messages?.[en]||(OBRA_DATA.editorLocale==='es'?es:en);
  let state = JSON.parse(JSON.stringify(OBRA_DATA.settings));
  if(!state.legal_links||Array.isArray(state.legal_links))state.legal_links={};
  function applyLegalDetection(){
    for(const key of ['privacy','legal','cookies']){
      const value=state.legal_links[key];
      const automatic=!value||value.mode==='auto'||(value.mode==='text'&&!value.text?.replace(/<[^>]*>/g,'').trim());
      if(!automatic||(key==='privacy'&&state.privacy_url))continue;
      const found=OBRA_DATA.legalDetected[key];
      if(found)state.legal_links[key]=found.source==='content'?{mode:'post',post:found.post,url:'',text:''}:{mode:'auto',post:0,url:'',text:''};
      else if(!(key==='privacy'&&state.privacy_url))state.legal_links[key]={mode:'text',post:0,url:'',text:''};
    }
  }
  applyLegalDetection();
  const foundNotice=document.getElementById('obra-legal-found');
  let foundSignature='';
  const acknowledgedPolicies=new Set();
  foundNotice.addEventListener('cancel',e=>e.preventDefault());
  foundNotice.querySelector('[data-legal-found-ok]').onclick=()=>{acknowledgedPolicies.add(foundSignature);try{sessionStorage.setItem('freesoon-policy:'+OBRA_DATA.siteUrl+':'+foundSignature,'1');}catch(e){}foundNotice.close();};
  function notifyDetectedPolicy(){
    const found=OBRA_DATA.legalDetected.privacy,v=legalValue('privacy');
    if(!found)return;
    const connected=v.mode==='auto'||(v.mode==='post'&&Number(v.post)===Number(found.post))||(v.mode==='url'&&v.url===found.url);
    if(!connected)return;
    const signature=JSON.stringify([found.source,found.post,found.url,found.title,OBRA_DATA.editorLocale]);
    if(acknowledgedPolicies.has(signature)||document.querySelector('dialog[open]'))return;
    try{if(sessionStorage.getItem('freesoon-policy:'+OBRA_DATA.siteUrl+':'+signature))return;}catch(e){}
    foundSignature=signature;
    const messages={content:tr('Hemos encontrado una página o un artículo publicado y lo hemos conectado al footer de esta pantalla mediante «Enlazar página o artículo».','We found a published page or post and connected it to this screen’s footer using “Link a page or post”.'),menu:tr('Hemos encontrado el enlace de la política de privacidad en un menú y lo hemos conectado al footer de esta pantalla.','We found the privacy policy link in a menu and connected it to this screen’s footer.'),wordpress:tr('Hemos conectado al footer de esta pantalla la política de privacidad configurada en WordPress.','We connected the privacy policy configured in WordPress to this screen’s footer.')};
    foundNotice.querySelector('[data-legal-found-message]').textContent=messages[found.source]||messages.wordpress;
    const link=foundNotice.querySelector('[data-legal-found-link]');link.textContent=found.title||found.url;link.href=found.url;
    foundNotice.showModal();
  }
  const warning=document.getElementById('obra-legal-warning'),legalEditor=document.getElementById('obra-legal-editor');
  let privacyAcknowledged=false,editingLegal=null;
  const legalValue=key=>state.legal_links[key]||{mode:'auto',post:0,url:'',text:''};
  function legalAvailable(key){const v=legalValue(key);return v.mode==='auto'?!!(OBRA_DATA.legalDetected[key]||(key==='privacy'&&state.privacy_url)):v.mode==='post'?!!Number(v.post):v.mode==='url'?/^https?:\/\//.test(v.url):v.mode==='text'?!!v.text.replace(/<[^>]*>/g,'').trim():false;}
  function syncLegal(){
    const enabled=state.show_form&&(state.form_email||state.form_phone);
    root.querySelector('[data-legal-section]').hidden=!enabled&&!legalAvailable('privacy');
    root.querySelectorAll('[data-legal]').forEach(row=>{const key=row.dataset.legal,v=legalValue(key);const choice=row.querySelector('[data-legal-choice="'+v.mode+'"]');row.querySelector('[data-legal-selected]').replaceChildren(...Array.from(choice.children).slice(0,2).map(el=>el.cloneNode(true)));row.querySelectorAll('[data-legal-choice]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.legalChoice===v.mode)));row.querySelector('[data-legal-post]').hidden=v.mode!=='post';row.querySelector('[data-legal-post]').value=v.post||0;row.querySelector('[data-legal-url]').hidden=v.mode!=='url';if(document.activeElement!==row.querySelector('[data-legal-url]'))row.querySelector('[data-legal-url]').value=v.url||'';row.querySelector('[data-legal-edit]').hidden=v.mode!=='text';row.querySelector('[data-legal-status]').textContent=v.mode==='auto'?(legalAvailable(key)?tr('Enlace encontrado; se incluye en el footer.','Link found; included in the footer.'):tr('No se ha encontrado.','Not found.')):'';});
    if(enabled&&!legalAvailable('privacy')&&!privacyAcknowledged&&!document.querySelector('dialog[open]'))warning.showModal();
    if(!enabled)privacyAcknowledged=false;
    if(!warning.open)notifyDetectedPolicy();
    if(!legalAvailable('privacy')&&OBRA_DATA.privacyCandidates>1)root.querySelector('[data-legal="privacy"] [data-legal-status]').textContent=tr('Hay varias páginas o artículos que podrían ser la política de privacidad. Elige el documento correcto en «Enlazar página o artículo».','Several pages or posts could be the privacy policy. Choose the correct document using “Link a page or post”.');
  }
  root.querySelectorAll('[data-legal-choice]').forEach(button=>button.onclick=()=>{const row=button.closest('[data-legal]');state.legal_links[row.dataset.legal]={...legalValue(row.dataset.legal),mode:button.dataset.legalChoice};row.querySelector('.obra-legal-picker').open=false;row.querySelector('summary').focus();render();});
  document.addEventListener('click',e=>root.querySelectorAll('.obra-legal-picker[open]').forEach(p=>{if(!p.contains(e.target))p.open=false;}));
  root.addEventListener('keydown',e=>{if(e.key==='Escape')root.querySelectorAll('.obra-legal-picker[open]').forEach(p=>{p.open=false;p.querySelector('summary').focus();});});
  warning.addEventListener('cancel',e=>e.preventDefault());
  document.querySelector('[data-legal-ok]').onclick=()=>{privacyAcknowledged=true;warning.close();root.querySelector('[data-legal-section]').open=true;};
  root.querySelectorAll('[data-legal]').forEach(row=>{row.addEventListener('change',e=>{const key=row.dataset.legal;state.legal_links[key]={...legalValue(key)};if(e.target.matches('[data-legal-mode]'))state.legal_links[key].mode=e.target.value;if(e.target.matches('[data-legal-post]'))state.legal_links[key].post=Number(e.target.value);if(e.target.matches('[data-legal-url]'))state.legal_links[key].url=e.target.value;render();});row.querySelector('[data-legal-edit]').onclick=()=>{editingLegal=row.dataset.legal;const value=legalValue(editingLegal).text||'';document.getElementById('obra_legal_text').value=value;window.tinymce?.get('obra_legal_text')?.setContent(value);legalEditor.showModal();};});
  document.querySelector('[data-legal-cancel]').onclick=()=>legalEditor.close();
  document.querySelector('[data-legal-apply]').onclick=()=>{const ed=window.tinymce?.get('obra_legal_text');state.legal_links[editingLegal]={...legalValue(editingLegal),text:ed&&!ed.isHidden()?ed.getContent():document.getElementById('obra_legal_text').value};legalEditor.close();render();};
  if(!['center','split','editorial','bottom'].includes(state.template))state.template='center';
  const contentKeys=['title','text','eyebrow','button_text','seo_title','seo_description','email_placeholder','name_placeholder','phone_placeholder'];
  if(!state.custom_content||Array.isArray(state.custom_content))state.custom_content={};
  const markCustom=key=>{state.custom_content[key]=true;};
  state.content_language='auto';
  const language=()=>OBRA_DATA.locale||'es';
  const remember=()=>{state.translations[language()]=Object.fromEntries(contentKeys.map(k=>[k,state[k]]));};
  const defaults={
    en:['Something wonderful is coming.','We are putting the finishing touches on our new website. Be the first to know.','A NEW CHAPTER','Notify me'],
    fr:['Une nouvelle aventure commence.','Notre nouveau site arrive bientôt. Soyez les premiers informés.','BIENTÔT','Me prévenir'],
    de:['Etwas Neues entsteht.','Unsere neue Website ist bald verfügbar. Bleiben Sie informiert.','BALD VERFÜGBAR','Benachrichtigen'],
    it:['Qualcosa di nuovo sta arrivando.','Il nostro nuovo sito sarà presto online. Resta aggiornato.','PROSSIMAMENTE','Avvisami'],
    pt:['Algo novo está a chegar.','O nosso novo site estará disponível em breve. Fique a par.','EM BREVE','Avise-me'],
    es:['Algo nuevo está por llegar.','Estamos preparando nuestra nueva web. Te avisaremos cuando esté lista.','MUY PRONTO','Avísame']
  };
  if(!state.translations || Array.isArray(state.translations))state.translations={};
  const frame = root.querySelector('iframe');
  function acceptLogoAnalysis(){
    const img=frame.contentDocument?.querySelector('.obra-logo');
    if(!state.logo_force && img?.dataset.originalReadable){
      const needed=img.dataset.originalReadable==='false';
      if(state.logo_smart!==needed){
        state.logo_smart=needed;
        root.querySelector('[data-field="logo_smart"]').checked=needed;
        toast(needed?tr('Contraste del logo activado para mejorar su lectura.','Logo contrast enabled to improve readability.'):tr('Contraste del logo desactivado: el original ya se distingue bien sobre este fondo.','Logo contrast disabled: the original is already readable on this background.'));
      }
    }
    root.querySelector('[data-logo-adjusted]').hidden=!state.logo_smart;
    updateLogoCrop();
  }
  const logoURL=()=>state.logo_source==='none'?'':state.logo_source==='site'?(state.site_logo_snapshot||''):state.logo;
  const logoDrop=root.querySelector('.obra-logo-drop');
  logoDrop.setAttribute('aria-label',tr('Seleccionar o cambiar logo','Select or change logo'));
  let logoUploadBusy=false;
  async function uploadLogo(file){
    if(state.logo_source!=='custom'||logoUploadBusy)return;
    if(!file||!['image/png','image/jpeg','image/webp','image/gif','image/avif'].includes(file.type)){toast(tr('Selecciona una imagen PNG, JPG, WebP, GIF o AVIF.','Choose a PNG, JPG, WebP, GIF or AVIF image.'),true);return;}
    logoUploadBusy=true;logoDrop.setAttribute('aria-busy','true');
    const data=new FormData();data.set('action','upload-attachment');data.set('_wpnonce',OBRA_DATA.uploadNonce);data.set('async-upload',file);
    try{const r=await fetch(OBRA_DATA.ajax,{method:'POST',body:data});const result=await r.json();if(!result.success)throw Error(result.data?.message||tr('No se pudo subir la imagen','Image upload failed'));state.logo=result.data.url;render();toast(tr('Logo añadido a la biblioteca de medios','Logo added to the media library'));}
    catch(e){toast(e.message,true);}finally{logoUploadBusy=false;logoDrop.removeAttribute('aria-busy');}
  }
  ['dragenter','dragover'].forEach(name=>logoDrop.addEventListener(name,e=>{e.preventDefault();if(state.logo_source==='custom')logoDrop.classList.add('is-dragging');}));
  logoDrop.addEventListener('dragleave',()=>logoDrop.classList.remove('is-dragging'));
  logoDrop.addEventListener('drop',e=>{e.preventDefault();logoDrop.classList.remove('is-dragging');uploadLogo(e.dataTransfer.files[0]);});
  const backgroundDrop=root.querySelector('.obra-background-drop');
  let backgroundBusy=false;
  const backgroundKind=()=>state.background_type==='video'?'video':state.background_type==='image'?'image':state.background_media_kind||'image';
  function acceptBackground(asset){
    const kind=asset.type==='video'||String(asset.mime||'').startsWith('video/')?'video':asset.type==='image'||String(asset.mime||'').startsWith('image/')?'image':null;
    if(!kind){toast(tr('Selecciona una imagen o un vídeo.','Choose an image or video.'),true);return;}
    state.background_media_kind=kind;
    const key=kind==='video'?'background_video':'background_image';state[key]=asset.url;activateBackground(key,asset.url);render();
  }
  async function uploadBackground(file){
    if(backgroundBusy||!file)return;
    if(!/^(image|video)\//.test(file.type)){toast(tr('Selecciona una imagen o un vídeo compatible.','Choose a supported image or video.'),true);return;}
    backgroundBusy=true;backgroundDrop.disabled=true;backgroundDrop.setAttribute('aria-busy','true');
    const data=new FormData();data.set('action','upload-attachment');data.set('_wpnonce',OBRA_DATA.uploadNonce);data.set('async-upload',file);
    try{
      const response=await fetch(OBRA_DATA.ajax,{method:'POST',body:data});const result=await response.json();
      if(!result.success)throw Error(result.data?.message||tr('No se pudo subir el archivo','File upload failed'));
      acceptBackground({...result.data,type:file.type.split('/')[0]});
    }catch(error){toast(error.message,true);}finally{backgroundBusy=false;backgroundDrop.disabled=false;backgroundDrop.removeAttribute('aria-busy');}
  }
  ['dragenter','dragover'].forEach(name=>backgroundDrop.addEventListener(name,e=>{e.preventDefault();backgroundDrop.classList.add('is-dragging');}));
  backgroundDrop.addEventListener('dragleave',()=>backgroundDrop.classList.remove('is-dragging'));
  backgroundDrop.addEventListener('drop',e=>{e.preventDefault();backgroundDrop.classList.remove('is-dragging');uploadBackground(e.dataTransfer.files[0]);});
  const cropHost=root.querySelector('[data-logo-crop]');const cropShadow=cropHost.attachShadow({mode:'open'});
  function updateLogoCrop(){
    const doc=frame.contentDocument,img=doc?.querySelector('.obra-logo');cropShadow.replaceChildren();if(!img)return;
    const page=doc.querySelector('.obra-page'),rect=img.getBoundingClientRect(),width=cropHost.clientWidth||150,height=124;
    const scale=Math.min(1,(width-20)/(rect.width+24),(height-16)/(rect.height+24));
    const wrap=document.createElement('div');wrap.style.cssText='position:relative;width:100%;height:124px;overflow:hidden;border-radius:8px';
    doc.querySelectorAll('style').forEach(style=>cropShadow.append(style.cloneNode(true)));
    const clone=page.cloneNode(true);clone.style.cssText+=';position:absolute;box-sizing:border-box;width:'+page.getBoundingClientRect().width+'px;height:'+page.getBoundingClientRect().height+'px;min-height:0;transform-origin:0 0;transform:translate('+(width/2-(rect.x+rect.width/2)*scale)+'px,'+(height/2-(rect.y+rect.height/2)*scale)+'px) scale('+scale+');pointer-events:none';
    const originals=[page,...page.querySelectorAll('*')],copies=[clone,...clone.querySelectorAll('*')];
    originals.forEach((el,i)=>{const css=doc.defaultView.getComputedStyle(el);for(const prop of css)copies[i].style.setProperty(prop,css.getPropertyValue(prop));});
    clone.style.position='absolute';clone.style.margin='0';clone.style.left='0';clone.style.top='0';clone.style.transformOrigin='0 0';
    clone.style.transform='translate('+(width/2-(rect.x+rect.width/2)*scale)+'px,'+(height/2-(rect.y+rect.height/2)*scale)+'px) scale('+scale+')';
    clone.querySelectorAll('.obra-shell *').forEach(el=>{if(!el.classList.contains('obra-logo')&&!el.contains(clone.querySelector('.obra-logo')))el.style.visibility='hidden';});
    const sourceVideo=page.querySelector('video'),copyVideo=clone.querySelector('video');
    if(sourceVideo?.readyState>=2&&copyVideo){
      try{const canvas=document.createElement('canvas');canvas.width=sourceVideo.videoWidth;canvas.height=sourceVideo.videoHeight;canvas.getContext('2d').drawImage(sourceVideo,0,0);const still=document.createElement('img');still.src=canvas.toDataURL();still.style.cssText=copyVideo.style.cssText;copyVideo.replaceWith(still);}catch(e){}
    }
    wrap.append(clone);cropShadow.append(wrap);
  }
  frame.addEventListener('load',()=>{
    updateLogoCrop();
    const video=frame.contentDocument?.querySelector('video');
    const videoError=()=>toast(tr('El navegador no puede reproducir este vídeo. Prueba WebM o MP4 con un códec compatible.','Your browser cannot play this video. Try WebM or MP4 with a supported codec.'),true);
    if(video?.error)videoError();else video?.addEventListener('error',videoError,{once:true});
    frame.contentDocument?.addEventListener('obra-logo-adjusted',acceptLogoAnalysis);acceptLogoAnalysis();
    frame.contentDocument?.querySelectorAll('img').forEach(img=>img.addEventListener('load',updateLogoCrop,{once:true}));
    frame.contentDocument?.querySelectorAll('[data-obra-edit]').forEach(el=>{
      el.addEventListener('input',()=>{
        const key=el.dataset.obraEdit;
        if(!contentKeys.includes(key))return;
        clearTimeout(previewTimer);
        ++previewRequest; // Discard any older render response while the user types.
        state[key]=el.innerText;markCustom(key);
        remember();
        const field=root.querySelector('[data-field="'+key+'"]');
        if(field)field.value=state[key];
      });
    });
  });
  const fontInput=root.querySelector('[data-font-search]'),fontList=root.querySelector('#obra-font-options');
  const fontOptions=OBRA_DATA.fonts||[];
  let filteredFonts=[],fontIndex=-1;
  function closeFonts(){fontList.hidden=true;fontInput.setAttribute('aria-expanded','false');fontInput.removeAttribute('aria-activedescendant');fontInput.value=fontOptions.find(f=>f.id===state.font)?.name||state.font;}
  function chooseFont(font){state.font=font.id;closeFonts();render();}
  function showFonts(query=''){
    fontInput.closest('label').classList.toggle('opens-up',root.querySelector('.obra-sidebar').getBoundingClientRect().bottom-fontInput.getBoundingClientRect().bottom<240);
    filteredFonts=fontOptions.filter(f=>(f.name+' '+f.family+' '+f.source).toLocaleLowerCase().includes(query.toLocaleLowerCase()));
    fontIndex=-1;fontInput.removeAttribute('aria-activedescendant');fontList.replaceChildren();
    filteredFonts.forEach((font,i)=>{
      const option=document.createElement('div');option.id='obra-font-'+i;option.setAttribute('role','option');option.setAttribute('aria-selected',String(font.id===state.font));option.className='obra-font-option';
      const name=document.createElement('span');name.textContent=font.name;const source=document.createElement('small');source.textContent=font.source;option.append(name,source);
      option.addEventListener('mousedown',e=>e.preventDefault());option.addEventListener('click',()=>chooseFont(font));fontList.append(option);
    });
    if(!filteredFonts.length){const empty=document.createElement('p');empty.textContent=tr('No hay fuentes que coincidan','No matching fonts');fontList.append(empty);}
    fontList.hidden=false;fontInput.setAttribute('aria-expanded','true');
  }
  fontInput.addEventListener('focus',()=>showFonts());
  fontInput.addEventListener('click',()=>{if(fontList.hidden)showFonts();});
  fontInput.addEventListener('input',()=>showFonts(fontInput.value));
  fontInput.addEventListener('blur',closeFonts);
  fontInput.addEventListener('keydown',e=>{
    if(e.key==='Escape'){closeFonts();return;}
    if(e.key==='Enter'){e.preventDefault();if(filteredFonts[fontIndex>=0?fontIndex:0]&&!fontList.hidden)chooseFont(filteredFonts[fontIndex>=0?fontIndex:0]);return;}
    if(!['ArrowDown','ArrowUp'].includes(e.key))return;
    e.preventDefault();if(fontList.hidden)showFonts();
    if(!filteredFonts.length)return;
    fontIndex=fontIndex<0?(e.key==='ArrowDown'?0:filteredFonts.length-1):(fontIndex+(e.key==='ArrowDown'?1:-1)+filteredFonts.length)%filteredFonts.length;
    [...fontList.children].forEach((el,i)=>el.classList.toggle('is-focused',i===fontIndex));
    const active=fontList.children[fontIndex];fontInput.setAttribute('aria-activedescendant',active.id);active.scrollIntoView({block:'nearest'});
  });
  const icon=(name)=>{const el=document.createElement('i');el.dataset.lucide=name;el.setAttribute('aria-hidden','true');return el;};
  const fieldIcons={form_name:'user',form_email:'mail',form_phone:'phone',name_placeholder:'user',email_placeholder:'mail',phone_placeholder:'phone',logo_trim:'crop',background_fit:'maximize',background_position:'scan',logo_source:'image',logo_smart:'contrast',template:'panels-top-left',background_type:'layers',color_bg:'paint-bucket',color_text:'type',background_image:'image',background_video:'film',overlay:'sun-moon',font:'type',gradient:'blend',motion:'waves',smart_text:'contrast',content_language:'languages',eyebrow:'heading',title:'heading-1',text:'align-left',logo:'image',launch_date:'calendar-clock',button_text:'mouse-pointer-2',auto_open:'calendar-check',show_countdown:'timer',show_form:'mail',show_language_switch:'languages',notify:'bell',notification_email:'mail',privacy_url:'shield-check',mode:'construction',allow_logged_in:'user-check',exclude_paths:'list-filter',custom_css:'code',seo_title:'search',seo_description:'text-search',social_instagram:'instagram',social_linkedin:'linkedin',social_x:'at-sign',enabled:'power'};
  root.querySelectorAll('label').forEach(label=>{
    const field=label.querySelector('[data-field]');const heading=document.createElement('span');heading.className='obra-field-heading';
    heading.append(icon((label.classList.contains('obra-font-picker')?'type':fieldIcons[field?.dataset.field])||(label.querySelector('[data-bypass]')?'key-round':'settings-2')));
    [...label.childNodes].filter(n=>n.nodeType===Node.TEXT_NODE&&n.textContent.trim()).forEach(n=>heading.append(n));
    label.prepend(heading);
  });
  function cards(key,names){
    const select=root.querySelector('[data-field="'+key+'"]');select.hidden=true;
    const holder=select.closest('label');const group=document.createElement('div');group.className='obra-choice-grid';group.setAttribute('role','group');
    group.setAttribute('aria-label',holder.textContent.trim());holder.after(group);holder.classList.add('obra-choice-label');
    [...select.options].forEach((option,i)=>{const button=document.createElement('button');button.type='button';button.dataset.choice=key;button.dataset.value=option.value;button.append(icon(names[i]));const title=document.createElement('small');title.textContent=option.text;button.append(title);group.append(button);});
  }
  cards('template',['align-center','layout-template','columns-2','panel-bottom']);
  cards('background_type',['paint-bucket','image','film']);
  const visualPickers=[];
  for(const [key,icons] of Object.entries({logo_source:['globe','image-plus','image-off'],background_fit:['maximize','scan','image','move-vertical','grid-2x2'],background_position:['arrow-up-left','arrow-up','arrow-up-right','arrow-left','crosshair','arrow-right','arrow-down-left','arrow-down','arrow-down-right']})){
    const select=root.querySelector('[data-field="'+key+'"]');select.hidden=true;
    const holder=select.closest('label');
    const picker=document.createElement('details');picker.className='obra-legal-picker obra-setting-picker';picker.dataset.picker=key;
    const summary=document.createElement('summary');summary.setAttribute('aria-label',holder.querySelector('.obra-field-heading')?.textContent||({logo_source:'Logo',background_fit:tr('Ajuste','Fit'),background_position:tr('Encuadre','Position')}[key]));
    const selected=document.createElement('span');selected.dataset.legalSelected='';summary.append(selected,icon('chevron-down'));picker.append(summary);
    const options=document.createElement('div');options.className='obra-legal-options';options.setAttribute('role','group');
    [...select.options].forEach((option,i)=>{const b=document.createElement('button');b.type='button';b.dataset.selectValue=option.value;b.append(icon(icons[i]));const text=document.createElement('span');text.textContent=option.text;b.append(text);const check=icon('check');check.classList.add('obra-selected-check');b.append(check);b.onclick=()=>{select.value=option.value;select.dispatchEvent(new Event('change',{bubbles:true}));picker.open=false;summary.focus();};options.append(b);});picker.append(options);holder.append(picker);
    picker.addEventListener('toggle',()=>{if(picker.open)root.querySelectorAll('.obra-legal-picker[open]').forEach(other=>{if(other!==picker)other.open=false;});});
    visualPickers.push({select,picker,selected});
  }
  function syncVisualPickers(){for(const {select,picker,selected} of visualPickers){picker.querySelectorAll('[data-select-value]').forEach(b=>{const option=[...select.options].find(o=>o.value===b.dataset.selectValue);b.disabled=option.disabled;b.setAttribute('aria-pressed',String(option.value===select.value));if(option.value===select.value)selected.replaceChildren(...Array.from(b.children).slice(0,2).map(el=>el.cloneNode(true)));});}}
  root.querySelectorAll('summary').forEach((el,i)=>{if(!el.closest('.obra-legal-picker')&&!el.querySelector('[data-lucide]'))el.prepend(icon(['languages','users','code','search'][i]||'settings-2'));});
  root.querySelectorAll('button').forEach(el=>{
    if(el.querySelector('[data-lucide]')||el.hasAttribute('data-background-picker'))return;
    const name=el.hasAttribute('data-background-remove')||el.hasAttribute('data-logo-remove')?'x':el.dataset.device?({desktop:'monitor',tablet:'tablet',mobile:'smartphone'}[el.dataset.device]):el.dataset.tab?({content:'panels-top-left',design:'palette',settings:'settings-2'}[el.dataset.tab]):el.classList.contains('obra-logo-drop')?'image-plus':el.dataset.media?'folder-open':el.hasAttribute('data-copy')?'copy':el.classList.contains('obra-save')?'save':'external-link';
    el.prepend(icon(name));
  });
  window.lucide.createIcons();
  function activateBackground(key,value){
    const type={color_bg:'gradient',background_image:'image',background_video:'video'}[key];
    if(!type||!String(value).trim()||state.background_type===type)return;
    state.background_type=type;
    toast(tr('Fondo cambiado a ','Background changed to ')+({gradient:tr('color','color'),image:tr('imagen','image'),video:tr('vídeo','video')}[type]));
  }
  const esc = (v='') => String(v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
  const fonts = {system:'Inter,system-ui,sans-serif',geometric:'Avenir Next,Futura,sans-serif',mono:'ui-monospace,Menlo,monospace',serif:'Iowan Old Style,Baskerville,Georgia,serif'};
  const templateDefaults = {
    atelier:{color_bg:'#0c0d10',color_accent:'#d9ff57',color_text:'#f5f4ef',font:'system',background_type:'gradient'},
    blueprint:{color_bg:'#123c69',color_accent:'#ffcf4a',color_text:'#f5f8ff',font:'mono',background_type:'gradient'},
    signal:{color_bg:'#ff4f45',color_accent:'#171717',color_text:'#171717',font:'geometric',background_type:'gradient'},
    horizon:{color_bg:'#d8cbbc',color_accent:'#803c2f',color_text:'#2b2521',font:'serif',background_type:'gradient'}
  };
  let previewTimer, previewRequest = 0;
  function refreshPreview() {
    clearTimeout(previewTimer);
    const request = ++previewRequest;
    previewTimer = setTimeout(async () => {
      try {
        const response = await fetch(OBRA_DATA.ajax + '?action=obra_preview_render&nonce=' + OBRA_DATA.nonce, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({settings:state})});
        if (!response.ok) throw new Error(tr('No se pudo actualizar la vista previa','Could not update preview'));
        const markup = await response.text();
        if (request === previewRequest) frame.srcdoc = markup;
      } catch (error) { toast(error.message, true); }
    }, 180);
  }
  let logoContext;
  function render() {
    if(!state.custom_content.text&&OBRA_DATA.defaultCopy){const mode=!state.show_form?'none':state.form_email?(state.form_phone?'both':'email'):(state.form_phone?'phone':'none');state.text=OBRA_DATA.defaultCopy[mode];}
    syncLegal();
    const contactEnabled=!!state.form_email||!!state.form_phone;
    if(!contactEnabled)state.form_name=false;
    const nameToggle=root.querySelector('[data-field="form_name"]');nameToggle.disabled=!contactEnabled;nameToggle.closest('label').classList.toggle('obra-control-disabled',!contactEnabled);
    for(const kind of ['email','name','phone'])root.querySelector('[data-field="'+kind+'_placeholder"]').closest('label').hidden=!state['form_'+kind];
    const tileOption=root.querySelector('[data-field="background_fit"] option[value="tile"]');
    tileOption.disabled=state.background_type==='video';
    if(state.background_type==='video'&&state.background_fit==='tile')state.background_fit='cover';
    const context=JSON.stringify(['logo','logo_source','logo_trim','background_type','background_image','background_video','color_bg','gradient','overlay','template','background_fit','background_position'].map(key=>state[key]));
    if(logoContext!==undefined && context!==logoContext)state.logo_force=false;
    logoContext=context;
    root.querySelector('[data-logo-adjusted]').hidden=!state.logo_smart;
    const kind=backgroundKind(),resource=state[kind==='video'?'background_video':'background_image'];
    const hasMedia=!!resource&&state.background_type!=='gradient';
    root.querySelector('[data-background-framing]').hidden=!hasMedia;
    const imageThumb=root.querySelector('[data-background-thumbnail]'),videoThumb=root.querySelector('[data-background-thumbnail-video]');
    imageThumb.hidden=!resource||kind!=='image';videoThumb.hidden=!resource||kind!=='video';
    if(resource&&kind==='image'&&imageThumb.getAttribute('src')!==resource)imageThumb.src=resource;
    if(resource&&kind==='video'&&videoThumb.getAttribute('src')!==resource)videoThumb.src=resource;
    root.querySelector('[data-background-placeholder]').hidden=!!resource;
    root.querySelector('[data-background-remove]').hidden=!resource;
    let resourceName='';try{resourceName=decodeURIComponent(new URL(resource).pathname.split('/').pop());}catch(e){}
    root.querySelector('[data-background-name]').textContent=resource?(kind==='video'?tr('Vídeo · ','Video · '):tr('Imagen · ','Image · '))+resourceName:tr('PNG, JPG, WebP, GIF, AVIF o vídeo','PNG, JPG, WebP, GIF, AVIF or video');
    backgroundDrop.classList.toggle('has-media',!!resource);
    const url=logoURL();logoDrop.classList.toggle('has-media',!!url);logoDrop.disabled=state.logo_source!=='custom';
    root.querySelector('[data-logo-thumbnail]').hidden=!url;
    if(url)root.querySelector('[data-logo-thumbnail]').src=url;
    root.querySelector('[data-logo-placeholder]').hidden=!!url;
    root.querySelector('[data-logo-placeholder]').textContent=state.logo_source==='custom'?tr('Arrastra una imagen aquí o pulsa para seleccionar','Drop an image here or click to choose'):state.logo_source==='site'?tr('Sin logo del sitio','No site logo'):tr('Sin logo','No logo');
    root.querySelector('[data-logo-help]').textContent=state.logo_source==='site'?(url?tr('Se utiliza el logo configurado en WordPress.','Using the logo configured in WordPress.'):tr('WordPress no tiene un logo configurado. Elige Logo personalizado para añadir uno.','WordPress has no site logo. Choose Custom logo to add one.')):state.logo_source==='none'?tr('No se mostrará ningún logo.','No logo will be shown.'):tr('Pulsa la imagen para cambiarla o arrastra un archivo.','Click the image to change it or drop a file.');
    root.querySelector('[data-logo-remove]').hidden=state.logo_source!=='custom'||!state.logo;
    root.querySelector('[data-logo-style]').hidden=!url;
    if(url)root.querySelector('[data-logo-original]').src=url;
    state.motion=!!state.gradient&&!!state.motion_preference;
    if(document.activeElement!==fontInput)fontInput.value=fontOptions.find(f=>f.id===state.font)?.name||state.font;
    refreshPreview();
    root.querySelectorAll('[data-field]').forEach(el => {const k=el.dataset.field;if(el.type==='checkbox')el.checked=!!state[k];else if(document.activeElement!==el)el.value=state[k]??'';});
    syncVisualPickers();
    root.querySelector('[data-bypass]').value=OBRA_DATA.siteUrl+'?obra_bypass='+state.bypass_token;
    root.querySelector('[data-output="overlay"]').value=state.overlay+'%';
    root.querySelector('[data-field="color_text"]').disabled=state.smart_text;
    const textColor=root.querySelector('[data-field="color_text"]');
    textColor.closest('label').classList.toggle('obra-control-disabled',!!state.smart_text);
    textColor.closest('label').title=state.smart_text?tr('Desactiva Contraste inteligente para elegir el color del texto.','Disable smart contrast to choose the text color.'):'';
    const motion=root.querySelector('[data-field="motion"]');
    motion.disabled=!state.gradient;
    motion.closest('label').classList.toggle('obra-control-disabled',!state.gradient);
    motion.closest('label').title=!state.gradient?tr('Activa Gradiente para habilitar Movimiento.','Enable Gradient to enable Motion.'):'';
    root.querySelectorAll('[data-choice]').forEach(el=>{const active=state[el.dataset.choice]===el.dataset.value;el.classList.toggle('is-active',active);el.setAttribute('aria-pressed',String(active));});
    for(const [key,visible] of [['overlay',hasMedia],['launch_date',state.show_countdown||state.auto_open],['button_text',state.show_form]]) root.querySelector('[data-field="'+key+'"]').closest('label').hidden=!visible;
  }
  function update(e) {
    const k=e.target.dataset.field;if(!k)return;
    if(k==='logo_source'&&e.target.value==='site')state.site_logo_snapshot=OBRA_DATA.siteLogo||'';
    if(k==='motion')state.motion_preference=e.target.checked;
    if(k==='logo_smart')state.logo_force=true;
    if(k==='content_language'){
      remember();state[k]=e.target.value;
      const l=language();const translated=state.translations[l]||Object.fromEntries(contentKeys.map((key,i)=>[key,(defaults[l]||defaults.en)[i]]));
      Object.assign(state,translated);remember();
    }else{state[k]=e.target.type==='checkbox'?e.target.checked:e.target.value;if(contentKeys.includes(k)){markCustom(k);remember();}}
    activateBackground(k,state[k]);render();
  }
  root.addEventListener('input',e=>{if(e.target.tagName!=='SELECT')update(e);});
  root.addEventListener('change',e=>{if(e.target.tagName==='SELECT')update(e);});
  root.addEventListener('click', e => {
    if(e.target.closest('[data-background-picker]')){
      const picker=wp.media({title:tr('Imagen / vídeo de fondo','Background image / video'),library:{type:['image','video']},button:{text:tr('Usar como fondo','Use as background')},multiple:false});
      picker.on('select',()=>acceptBackground(picker.state().get('selection').first().toJSON()));picker.open();return;
    }
    if(e.target.closest('[data-background-remove]')){
      state[backgroundKind()==='video'?'background_video':'background_image']='';state.background_type='gradient';render();toast(tr('Recurso retirado. Fondo cambiado a color.','Media removed. Background changed to color.'));return;
    }
    if(e.target.closest('[data-logo-remove]')){state.logo='';render();return;}
    const choice=e.target.closest('[data-choice]');if(choice){state[choice.dataset.choice]=choice.dataset.value;if(choice.dataset.choice==='background_type'&&choice.dataset.value!=='gradient')state.background_media_kind=choice.dataset.value;render();return;}
    const tab=e.target.closest('[data-tab]');if(tab){root.querySelectorAll('[data-tab],[data-panel]').forEach(x=>x.classList.remove('is-active'));tab.classList.add('is-active');root.querySelector(`[data-panel="${tab.dataset.tab}"]`).classList.add('is-active');updateLogoCrop();return;}
    const tpl=e.target.closest('[data-template]');if(tpl){state={...state,...templateDefaults[tpl.dataset.template],template:tpl.dataset.template};render();return;}
    const dev=e.target.closest('[data-device]');if(dev){root.querySelectorAll('[data-device]').forEach(x=>x.classList.remove('is-active'));dev.classList.add('is-active');root.querySelector('[data-device-frame]').dataset.deviceFrame=dev.dataset.device;return;}
    const media=e.target.closest('[data-media]');if(media){e.preventDefault();const picker=wp.media({title:tr('Seleccionar recurso','Choose media'),library:{type:media.dataset.media==='background_video'?'video':'image'},button:{text:tr('Usar este archivo','Use this file')},multiple:false});picker.on('select',()=>{const key=media.dataset.media;state[key]=picker.state().get('selection').first().toJSON().url;activateBackground(key,state[key]);render();});picker.open();return;}
    if(e.target.closest('[data-copy]')){navigator.clipboard.writeText(root.querySelector('[data-bypass]').value);toast(tr('Enlace copiado','Link copied'));return;}
    if(e.target.closest('.obra-preview-link')){window.open(OBRA_DATA.previewUrl,'_blank','noopener');return;}
    if(e.target.closest('.obra-save'))save();
  });
  window.addEventListener('message', e=>{if(e.source!==frame.contentWindow || !['title','text','eyebrow'].includes(e.data?.obraEdit))return;state[e.data.obraEdit]=e.data.value;markCustom(e.data.obraEdit);remember();const field=root.querySelector(`[data-field="${e.data.obraEdit}"]`);if(field)field.value=e.data.value;});
  async function save(){const btn=root.querySelector('.obra-save');const buttonMarkup=btn.innerHTML;btn.disabled=true;btn.textContent=tr('Guardando…','Saving…');try{const res=await fetch(`${OBRA_DATA.ajax}?action=obra_save&nonce=${OBRA_DATA.nonce}`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({settings:state})});const data=await res.json();if(!data.success)throw Error(data.data?.message||tr('No se pudo guardar','Could not save'));state=data.data.settings;render();toast(tr('Cambios guardados','Changes saved'));}catch(err){toast(err.message,true)}finally{btn.disabled=false;btn.innerHTML=buttonMarkup;}}
  let toastTimer;
  function toast(msg,error=false){const t=root.querySelector('.obra-toast');t.replaceChildren();const symbol=icon(error?'circle-alert':'info');const text=document.createElement('span');text.textContent=msg;t.append(symbol,text);window.lucide.createIcons();clearTimeout(toastTimer);t.classList.toggle('is-error',error);t.classList.add('is-visible');toastTimer=setTimeout(()=>t.classList.remove('is-visible'),6000)}
  render();
  const logoNotice=document.getElementById('freesoon-logo-notice');let pendingLogo=null,checkingLogo=false;
  logoNotice.addEventListener('cancel',e=>e.preventDefault());
  async function checkSiteLogo(){if(checkingLogo||document.hidden||logoNotice.open)return;checkingLogo=true;try{const response=await fetch(OBRA_DATA.ajax+'?action=obra_site_logo&nonce='+OBRA_DATA.nonce);const result=await response.json();if(!result.success)return;pendingLogo=result.data;if(pendingLogo.pending&&!document.querySelector('dialog[open]')){logoNotice.querySelector('[data-detected-logo]').src=pendingLogo.url;logoNotice.showModal();}}catch(e){}finally{checkingLogo=false;}}
  async function decideLogo(use){const buttons=logoNotice.querySelectorAll('button');buttons.forEach(b=>b.disabled=true);try{const data=new URLSearchParams({action:'obra_logo_decision',nonce:OBRA_DATA.nonce,signature:pendingLogo.signature,use:use?'1':'0'});const response=await fetch(OBRA_DATA.ajax,{method:'POST',body:data});const result=await response.json();if(!result.success)throw Error();OBRA_DATA.siteLogo=pendingLogo.url;if(use){state.logo_source='site';state.site_logo_snapshot=pendingLogo.url;render();}logoNotice.close();}catch(e){logoNotice.querySelector('[data-logo-notice-error]').textContent=tr('No se pudo confirmar. Vuelve a intentarlo.','Could not confirm. Please try again.');}finally{buttons.forEach(b=>b.disabled=false);}}
  logoNotice.querySelector('[data-logo-accept]').onclick=()=>decideLogo(true);logoNotice.querySelector('[data-logo-decline]').onclick=()=>decideLogo(false);
  let checkingLegal=false;
  async function checkLegal(){
    if(checkingLegal||document.hidden)return;checkingLegal=true;
    try{const response=await fetch(OBRA_DATA.ajax+'?action=obra_legal_status&nonce='+OBRA_DATA.nonce);const result=await response.json();if(!result.success)return;
      const changed=JSON.stringify(OBRA_DATA.legalDetected)!==JSON.stringify(result.data.detected)||JSON.stringify(OBRA_DATA.legalPosts)!==JSON.stringify(result.data.posts);
      OBRA_DATA.privacyCandidates=result.data.candidates;
      if(changed){OBRA_DATA.legalDetected=result.data.detected;OBRA_DATA.legalPosts=result.data.posts;
        root.querySelectorAll('[data-legal-post]').forEach(select=>{const empty=new Option('—','0');select.replaceChildren(empty,...result.data.posts.map(post=>new Option(post.title,String(post.id))));});
        for(const key of ['privacy','legal','cookies']){const v=legalValue(key);if(v.mode==='post'&&!result.data.posts.some(post=>Number(post.id)===Number(v.post)))state.legal_links[key]={...v,post:0};}
        applyLegalDetection();if(legalAvailable('privacy')&&warning.open)warning.close();render();
      }else notifyDetectedPolicy();
    }catch(e){}finally{checkingLegal=false;}
  }
  setInterval(checkLegal,15000);window.addEventListener('focus',checkLegal);
  checkSiteLogo();setInterval(checkSiteLogo,10000);window.addEventListener('focus',checkSiteLogo);
})();

