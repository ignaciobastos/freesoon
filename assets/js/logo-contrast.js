(() => {
 const logo=document.querySelector('.obra-logo');
 if(!logo || !window.obraLogoContrast)return;
 const config=window.obraLogoContrast;
 const canvas=document.createElement('canvas');canvas.width=64;canvas.height=32;
 const ctx=canvas.getContext('2d',{willReadFrequently:true});
 const luminance=(r,g,b)=>{const a=[r,g,b].map(v=>{v/=255;return v<=.04045?v/12.92:((v+.055)/1.055)**2.4;});return .2126*a[0]+.7152*a[1]+.0722*a[2];};
 const ratio=(a,b)=>(Math.max(a,b)+.05)/(Math.min(a,b)+.05);
 let logoPixels;
 let trimAttempted=false;
 function trimLogo(){
  if(trimAttempted||!config.trim)return false;
  trimAttempted=true;
  try{
   const width=logo.naturalWidth,height=logo.naturalHeight;
   if(width*height>16777216){logo.dataset.trim='unavailable';return false;}
   const surface=document.createElement('canvas');surface.width=width;surface.height=height;
   const painter=surface.getContext('2d',{willReadFrequently:true});painter.drawImage(logo,0,0);
   const pixels=painter.getImageData(0,0,width,height).data;
   let left=width,top=height,right=-1,bottom=-1;
   for(let y=0;y<height;y++)for(let x=0;x<width;x++){
    if(pixels[(y*width+x)*4+3]===0)continue;
    left=Math.min(left,x);right=Math.max(right,x);top=Math.min(top,y);bottom=Math.max(bottom,y);
   }
   if(right<0 || (left===0&&top===0&&right===width-1&&bottom===height-1)){logo.dataset.trim='unchanged';return false;}
   const cropped=document.createElement('canvas');cropped.width=right-left+1;cropped.height=bottom-top+1;
   cropped.getContext('2d').drawImage(surface,left,top,cropped.width,cropped.height,0,0,cropped.width,cropped.height);
   logo.dataset.trim='cropped';logo.dataset.trimBounds=[left,top,cropped.width,cropped.height].join(',');
   logo.src=cropped.toDataURL('image/png');return true;
  }catch(error){logo.dataset.trim='unavailable';return false;}
 }

 function analyze(){
  if(!logo.complete||!logo.naturalWidth)return;
  if(trimLogo())return;
  try{
   ctx.clearRect(0,0,64,32);ctx.drawImage(logo,0,0,64,32);
   logoPixels=ctx.getImageData(0,0,64,32).data;
  }catch(e){logo.dataset.contrast='unavailable';return;}
  update();
 }
 function update(){
  if(!logoPixels)return;
  ctx.clearRect(0,0,64,32);ctx.fillStyle=config.color;ctx.fillRect(0,0,64,32);
  const media=document.querySelector('.background img,.background video');
  let available=true;
  if(media){
   const w=media.naturalWidth||media.videoWidth,h=media.naturalHeight||media.videoHeight;
   if(!w||!h)return;
   const box=media.getBoundingClientRect(),area=logo.getBoundingClientRect();
   const fit=config.fit==='tile'&&media.tagName==='VIDEO'?'cover':config.fit||'cover',pos=(config.position||'50% 50%').split(' ').map(v=>parseFloat(v)/100);
   const scale=fit==='original'||fit==='tile'?1:fit==='contain'?Math.min(box.width/w,box.height/h):fit==='height'?box.height/h:Math.max(box.width/w,box.height/h);
   const sx=(area.left-box.left+(w*scale-box.width)*pos[0])/scale;
   const sy=(area.top-box.top+(h*scale-box.height)*pos[1])/scale;
   try{
    if(fit==='tile'){
      for(let y=0;y<32;y++)for(let x=0;x<64;x++){
        const px=((sx+x*area.width/64)%w+w)%w,py=((sy+y*area.height/32)%h+h)%h;
        ctx.drawImage(media,px,py,1,1,x,y,1,1);
      }
    }else ctx.drawImage(media,sx,sy,area.width/scale,area.height/scale,0,0,64,32);
    ctx.getImageData(0,0,1,1);
   }
   catch(e){available=false;}
   if(!available){logo.dataset.contrast='unavailable';logo.style.filter='none';return;}
   ctx.fillStyle='rgba(0,0,0,'+config.overlay/100+')';ctx.fillRect(0,0,64,32);
  }else if(config.gradient){
   const gradient=ctx.createLinearGradient(0,0,64,32);gradient.addColorStop(0,config.color);gradient.addColorStop(1,config.dark);ctx.fillStyle=gradient;ctx.fillRect(0,0,64,32);
  }
  const bg=ctx.getImageData(0,0,64,32).data;let total=0,good=0,white=0,black=0;
  for(let i=0;i<logoPixels.length;i+=4){
   const alpha=logoPixels[i+3]/255;if(alpha<.75)continue;
   const backdrop=luminance(bg[i],bg[i+1],bg[i+2]);
   const original=luminance(logoPixels[i]*alpha+bg[i]*(1-alpha),logoPixels[i+1]*alpha+bg[i+1]*(1-alpha),logoPixels[i+2]*alpha+bg[i+2]*(1-alpha));
   total+=alpha;if(ratio(original,backdrop)>=3)good+=alpha;
   white+=ratio(1,backdrop)*alpha;black+=ratio(0,backdrop)*alpha;
  }
  const readable=!total||good/total>=.65;
  logo.dataset.originalReadable=String(readable);
  const active=config.force?config.enabled:!readable;
  const mode=!active?'original':white>=black?'white':'black';
  logo.style.filter=mode==='original'?'none':mode==='white'?'brightness(0) invert(1)':'brightness(0)';
  logo.dataset.contrast=mode;document.dispatchEvent(new Event('obra-logo-adjusted'));
 }
 logo.addEventListener('load',analyze);if(logo.complete)analyze();
 document.querySelectorAll('.background img,.background video').forEach(el=>{el.addEventListener('load',update);el.addEventListener('loadeddata',update);});
 window.addEventListener('resize',update);
 if(document.querySelector('.background video'))setInterval(()=>{if(!document.hidden)update();},1000);
})();
