document.addEventListener('DOMContentLoaded',()=>{
  const reduceMotion=window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

  // Máscaras e limites brasileiros. O servidor repete as validações por segurança.
  const digits=v=>(v||'').replace(/\D/g,'');
  const maskCpf=v=>{const n=digits(v).slice(0,11);return n.replace(/^(\d{3})(\d)/,'$1.$2').replace(/^(\d{3})\.(\d{3})(\d)/,'$1.$2.$3').replace(/(\d{3})(\d{1,2})$/,'$1-$2')};
  const maskPhone=v=>{const n=digits(v).slice(0,11);if(n.length<=2)return n?`(${n}`:'';if(n.length<=10)return `(${n.slice(0,2)}) ${n.slice(2,6)}${n.length>6?'-'+n.slice(6):''}`;return `(${n.slice(0,2)}) ${n.slice(2,7)}-${n.slice(7)}`};
  const cpfIsValid=value=>{const n=digits(value);if(n.length!==11||/^(\d)\1{10}$/.test(n))return false;for(let t=9;t<11;t++){let sum=0;for(let i=0;i<t;i++)sum+=Number(n[i])*((t+1)-i);let d=(10*sum)%11;if(d===10)d=0;if(Number(n[t])!==d)return false;}return true;};
  const phoneIsValid=value=>{const n=digits(value);if(n.length===10)return /^[1-9]{2}[2-5]\d{7}$/.test(n);if(n.length===11)return /^[1-9]{2}9\d{8}$/.test(n);return false;};
  document.querySelectorAll('[data-cpf]').forEach(input=>{const apply=()=>{input.value=maskCpf(input.value);const n=digits(input.value);input.setCustomValidity(n.length===0||cpfIsValid(n)?'':'Informe um CPF brasileiro válido com exatamente 11 dígitos.');};input.value=maskCpf(input.value);input.addEventListener('input',apply);input.addEventListener('blur',apply);apply();});
  document.querySelectorAll('[data-phone]').forEach(input=>{const apply=()=>{input.value=maskPhone(input.value);const n=digits(input.value);input.setCustomValidity(n.length===0||phoneIsValid(n)?'':'Use 10 dígitos para telefone fixo (DDD + número) ou 11 para celular (DDD + 9 + número).');};input.value=maskPhone(input.value);input.addEventListener('input',apply);input.addEventListener('blur',apply);apply();});

  // Após erro de formulário, volta exatamente ao formulário/posição e repõe apenas campos não sensíveis.
  const stateKey='aura-form-state:'+location.pathname;
  const errorOnPage=!!document.querySelector('.alert.error[data-popup="1"],.alert.warning[data-popup="1"]');
  let lastFocusedName='';
  document.addEventListener('focusin',e=>{if(e.target?.form&&e.target.name)lastFocusedName=e.target.name;});
  const saveFormState=form=>{
    try{
      const fields={};
      form.querySelectorAll('input,select,textarea').forEach(el=>{
        if(!el.name||el.type==='password'||el.type==='hidden'||el.type==='file')return;
        if(el.type==='checkbox'||el.type==='radio'){if(!fields[el.name])fields[el.name]=[];if(el.checked)fields[el.name].push(el.value);}
        else fields[el.name]=el.value;
      });
      const forms=[...document.forms],formIndex=forms.indexOf(form);
      const anchor=form.closest('[id]')?.id||form.id||'';
      const details=[...form.closest('details')?[form.closest('details')]:[]].filter(Boolean).map(d=>({id:d.id||'',open:d.open}));
      sessionStorage.setItem(stateKey,JSON.stringify({y:window.scrollY,fields,anchor,formIndex,focusName:lastFocusedName,details,at:Date.now()}));
    }catch(e){}
  };
  document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',()=>saveFormState(form)));
  try{
    const saved=JSON.parse(sessionStorage.getItem(stateKey)||'null');
    if(saved&&Date.now()-saved.at<180000){
      const forms=[...document.forms];
      const form=(Number.isInteger(saved.formIndex)&&forms[saved.formIndex])?forms[saved.formIndex]:(saved.anchor?document.getElementById(saved.anchor):null);
      if(errorOnPage&&form&&saved.fields){
        Object.entries(saved.fields).forEach(([name,value])=>{
          form.querySelectorAll(`[name="${CSS.escape(name)}"]`).forEach(el=>{
            if(el.type==='password'||el.type==='hidden'||el.type==='file')return;
            if(el.type==='checkbox'||el.type==='radio')el.checked=Array.isArray(value)&&value.includes(el.value);
            else el.value=Array.isArray(value)?(value[0]??''):value;
            el.dispatchEvent(new Event('change',{bubbles:true}));
          });
        });
        form.closest('details')?.setAttribute('open','');
      }
      setTimeout(()=>{
        const target=saved.anchor?document.getElementById(saved.anchor):form;
        if(errorOnPage&&target)target.scrollIntoView({block:'center',behavior:'auto'});else window.scrollTo({top:Number(saved.y)||0,behavior:'auto'});
        if(errorOnPage&&form&&saved.focusName){form.querySelector(`[name="${CSS.escape(saved.focusName)}"]`)?.focus({preventScroll:true});}
      },80);
      if(!errorOnPage)sessionStorage.removeItem(stateKey);
    }
  }catch(e){}

  const closeModal=(overlay)=>{
    if(!overlay)return;
    overlay.classList.remove('show');
    const remove=()=>overlay.remove();
    reduceMotion?remove():setTimeout(remove,180);
  };

  const popup=(msg,type='info',options={})=>{
    const previous=document.querySelector('.aura-modal-overlay');
    if(previous)previous.remove();
    const overlay=document.createElement('div');
    overlay.className='aura-modal-overlay';
    overlay.setAttribute('role','presentation');
    const title=options.title||(type==='success'?'Tudo certo':type==='error'?'Atenção':type==='warning'?'Confirmação':'Espaço Aura');
    const icon=type==='success'?'✓':type==='error'?'!':type==='warning'?'?':'i';
    overlay.innerHTML=`<div class="aura-modal ${type}" role="dialog" aria-modal="true" aria-labelledby="aura-modal-title"><button type="button" class="aura-modal-x" aria-label="Fechar">×</button><div class="aura-modal-icon">${icon}</div><h2 id="aura-modal-title"></h2><p class="aura-modal-message"></p><div class="aura-modal-actions"></div></div>`;
    overlay.querySelector('h2').textContent=title;
    overlay.querySelector('.aura-modal-message').textContent=msg;
    const actions=overlay.querySelector('.aura-modal-actions');
    const cancel=document.createElement('button');
    cancel.type='button';cancel.className='btn btn-outline';cancel.textContent=options.cancelText||'Cancelar';
    const ok=document.createElement('button');
    ok.type='button';ok.className='btn';ok.textContent=options.okText||'Entendi';
    if(options.confirm){actions.append(cancel,ok)}else actions.append(ok);
    document.body.appendChild(overlay);
    requestAnimationFrame(()=>overlay.classList.add('show'));
    const finish=(value)=>{closeModal(overlay);options.onClose?.(value)};
    ok.addEventListener('click',()=>finish(true));
    cancel.addEventListener('click',()=>finish(false));
    overlay.querySelector('.aura-modal-x').addEventListener('click',()=>finish(false));
    overlay.addEventListener('click',e=>{if(e.target===overlay)finish(false)});
    overlay.addEventListener('keydown',e=>{if(e.key==='Escape')finish(false)});
    setTimeout(()=>ok.focus(),0);
    return overlay;
  };
  window.AuraPopup=popup;

  // Toda mensagem do sistema vira pop-up central.
  document.querySelectorAll('.alert[data-popup="1"]').forEach(el=>{
    popup(el.textContent.trim(),el.classList.contains('error')?'error':el.classList.contains('success')?'success':el.classList.contains('warning')?'warning':'info');
    el.remove();
  });

  // Confirmações sem confirm() nativo.
  document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',e=>{
    if(form.dataset.confirmed==='1')return;
    e.preventDefault();
    popup(form.dataset.confirm||'Deseja continuar?','warning',{confirm:true,okText:form.dataset.confirmOk||'Confirmar',cancelText:'Cancelar',onClose:(yes)=>{if(yes){form.dataset.confirmed='1';form.requestSubmit();}}});
  }));

  // Olhinho automático em TODOS os inputs de senha existentes e futuros na página inicial.
  document.querySelectorAll('input[type="password"]').forEach(input=>{
    if(input.closest('.password-field'))return;
    const wrap=document.createElement('span');wrap.className='password-field';
    input.parentNode.insertBefore(wrap,input);wrap.appendChild(input);
    const eye=document.createElement('button');eye.type='button';eye.className='password-toggle';eye.setAttribute('aria-label','Mostrar senha');eye.setAttribute('aria-pressed','false');
    eye.innerHTML='<svg class="eye-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.8" fill="none" stroke="currentColor" stroke-width="1.8"/><path class="eye-slash" d="M4 4l16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
    wrap.appendChild(eye);
    eye.addEventListener('click',()=>{
      const show=input.type==='password';input.type=show?'text':'password';eye.setAttribute('aria-pressed',String(show));eye.setAttribute('aria-label',show?'Ocultar senha':'Mostrar senha');eye.classList.toggle('visible',show);
    });
  });

  // Feedback visual leve ao enviar formulários, sem bloquear confirmações.
  document.querySelectorAll('form:not([data-confirm])').forEach(form=>form.addEventListener('submit',()=>{
    const b=form.querySelector('button[type="submit"],button:not([type])');
    if(b && !b.disabled){b.classList.add('is-submitting');}
  }));

  const cargo=document.querySelector('#cargo'),vp=document.querySelector('#vinculoProf');
  if(cargo&&vp){const f=()=>{vp.style.display=cargo.value==='profissional'?'block':'none'};cargo.addEventListener('change',f);f()}

  if(window.AURA_AGENDAMENTO){
    const serv=document.querySelector('#servico'),prof=document.querySelector('#profissional'),data=document.querySelector('#data'),box=document.querySelector('#horarios'),btn=document.querySelector('#btnAgendar');
    const filtrar=()=>{const s=serv.value.toLowerCase();[...prof.options].forEach((o,i)=>{if(!i)return;o.hidden=!!s&&o.dataset.especialidade.toLowerCase()!==s});if(prof.selectedOptions[0]?.hidden)prof.value='';carregar()};
    const carregar=async()=>{btn.disabled=true;box.innerHTML='<p class="muted">Selecione profissional e data.</p>';if(!prof.value||!data.value)return;box.innerHTML='<p class="muted loading-soft">Carregando horários...</p>';try{const r=await fetch(`api/horarios.php?profissional_id=${encodeURIComponent(prof.value)}&data=${encodeURIComponent(data.value)}`);const j=await r.json();if(!j.ok)throw new Error(j.mensagem);if(!j.horarios.length){box.innerHTML='<div class="inline-note">Nenhum horário disponível nesta data.</div>';return}box.innerHTML='<div class="horarios">'+j.horarios.map(h=>`<label class="horario-option ${h.ocupado?'ocupado':''}"><input type="radio" name="horario" value="${h.horario}" ${h.ocupado?'disabled':''} required><span>${h.horario}${h.ocupado?' · ocupado':''}</span></label>`).join('')+'</div>';box.querySelectorAll('input:not(:disabled)').forEach(i=>i.addEventListener('change',()=>btn.disabled=false))}catch(e){box.innerHTML='<div class="inline-note error">Não foi possível carregar os horários.</div>';popup(e.message||'Erro ao carregar horários','error')}};
    serv.addEventListener('change',filtrar);prof.addEventListener('change',carregar);data.addEventListener('change',carregar);
  }

  const c=document.querySelector('[data-carousel]');
  if(c){const step=()=>Math.max(260,c.clientWidth*.72);document.querySelector('[data-carousel-prev]')?.addEventListener('click',()=>c.scrollBy({left:-step(),behavior:'smooth'}));document.querySelector('[data-carousel-next]')?.addEventListener('click',()=>c.scrollBy({left:step(),behavior:'smooth'}));}

  const sel=document.querySelector('#lembrete_opcao'),custom=document.querySelector('#reminderCustom');
  if(sel&&custom){const sync=()=>custom.classList.toggle('show',sel.value==='custom');sel.addEventListener('change',sync);sync();}


  // Entrada suave de blocos ao longo de todo o sistema. Só escondemos depois que o JS chegou com segurança até aqui.
  document.body.classList.add('motion-ready');
  const revealItems=[...document.querySelectorAll('.reveal-up,.reveal-down,.reveal-left,.reveal-right,.reveal-scale')];
  if(reduceMotion){revealItems.forEach(el=>el.classList.add('is-revealed'));}
  else if('IntersectionObserver' in window){
    const revealObserver=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting){entry.target.classList.add('is-revealed');revealObserver.unobserve(entry.target);}}),{threshold:.12,rootMargin:'0px 0px -5% 0px'});
    revealItems.forEach(el=>revealObserver.observe(el));
  }else revealItems.forEach(el=>el.classList.add('is-revealed'));

  // Efeito de digitação da chamada principal da landing page.
  document.querySelectorAll('[data-typewriter]').forEach(el=>{
    const output=el.querySelector('[data-typewriter-output]');if(!output)return;
    const full=(el.dataset.typewriter||'').replace('|','\n');
    if(reduceMotion){output.textContent=full;output.style.whiteSpace='pre-line';return;}
    output.style.whiteSpace='pre-line';let i=0;
    const type=()=>{output.textContent=full.slice(0,i);if(i<=full.length){i++;setTimeout(type,i===full.length+1?0:42+(i%3)*9);}};type();
  });

  // Indicador global de sessão: funciona na raiz e também nas subpastas.
  const scriptEl=[...document.scripts].find(s=>/\/js\/app\.js(?:\?|$)/.test(s.src));
  if(scriptEl){
    const appBase=new URL('../',scriptEl.src);
    fetch(new URL('api/session-status.php',appBase),{credentials:'same-origin',cache:'no-store'}).then(r=>r.ok?r.json():Promise.reject()).then(s=>{
      if(document.querySelector('.session-corner'))return;
      const a=document.createElement('a');a.className='session-corner'+(s.logged?' logged':'');
      a.href=new URL(s.logged?s.area:s.login,appBase).href;
      const title=s.logged?`Conectado como ${s.name}`:'Você não está conectado';
      const sub=s.logged?`${s.type} · acessar minha área`:'Entrar na área do paciente';
      a.innerHTML='<span class="session-dot" aria-hidden="true"></span><span class="session-copy"><strong></strong><small></small></span><span class="session-arrow" aria-hidden="true">›</span>';
      a.querySelector('strong').textContent=title;a.querySelector('small').textContent=sub;a.setAttribute('aria-label',title+'. '+sub);
      document.body.appendChild(a);requestAnimationFrame(()=>a.classList.add('show'));
    }).catch(()=>{});
  }

  // Contadores animados da diretoria.
  document.querySelectorAll('.count-up').forEach(el=>{
    const target=Math.max(0,Number(el.dataset.count)||0);if(reduceMotion){el.textContent=String(target);return;}
    let started=false;const run=()=>{if(started)return;started=true;const start=performance.now(),duration=850;const tick=now=>{const p=Math.min(1,(now-start)/duration),e=1-Math.pow(1-p,3);el.textContent=Math.round(target*e).toLocaleString('pt-BR');if(p<1)requestAnimationFrame(tick)};requestAnimationFrame(tick)};
    if('IntersectionObserver' in window){const o=new IntersectionObserver(es=>{if(es.some(x=>x.isIntersecting)){run();o.disconnect()}},{threshold:.4});o.observe(el)}else run();
  });

  // Barras executivas animadas e interativas.
  document.querySelectorAll('.metric-bar').forEach(bar=>{
    const animate=()=>bar.classList.add('is-animated');
    if(reduceMotion)animate();else if('IntersectionObserver' in window){const o=new IntersectionObserver(es=>{if(es.some(x=>x.isIntersecting)){animate();o.disconnect()}},{threshold:.35});o.observe(bar)}else animate();
    bar.addEventListener('click',()=>{document.querySelectorAll('.metric-bar.is-selected').forEach(x=>x.classList.remove('is-selected'));bar.classList.add('is-selected');});
  });

  // Gráfico de linha SVG da diretoria, com pontos e tooltip interativos.
  document.querySelectorAll('.director-line-chart').forEach(chart=>{
    const svg=chart.querySelector('svg');if(!svg)return;let labels=[],values=[];try{labels=JSON.parse(chart.dataset.labels||'[]');values=JSON.parse(chart.dataset.values||'[]')}catch(e){return}
    if(!values.length){svg.innerHTML='<text x="20" y="40" class="chart-axis-label">Ainda não há dados suficientes.</text>';return}
    const W=700,H=250,pad={l:38,r:18,t:18,b:38},max=Math.max(1,...values),min=0,span=Math.max(1,values.length-1);
    const x=i=>pad.l+(W-pad.l-pad.r)*(i/span),y=v=>H-pad.b-(H-pad.t-pad.b)*((v-min)/(max-min||1));
    const pts=values.map((v,i)=>[x(i),y(v)]);const d=pts.map((p,i)=>(i?'L':'M')+p[0].toFixed(1)+' '+p[1].toFixed(1)).join(' ');const area=d+` L ${pts.at(-1)[0]} ${H-pad.b} L ${pts[0][0]} ${H-pad.b} Z`;
    let grid='';for(let i=0;i<4;i++){const gy=pad.t+(H-pad.t-pad.b)*(i/3);grid+=`<line class="chart-grid-line" x1="${pad.l}" y1="${gy}" x2="${W-pad.r}" y2="${gy}"/>`}
    const defs='<defs><linearGradient id="auraArea" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#008b67" stop-opacity=".22"/><stop offset="1" stop-color="#008b67" stop-opacity=".015"/></linearGradient></defs>';
    const axes=labels.map((l,i)=>`<text class="chart-axis-label" text-anchor="middle" x="${x(i)}" y="${H-12}">${String(l).replace(/[<>&]/g,'')}</text>`).join('');
    const dots=pts.map((p,i)=>`<circle class="chart-dot" tabindex="0" role="button" aria-label="${labels[i]}: ${values[i]} consultas" data-i="${i}" cx="${p[0]}" cy="${p[1]}" r="5"/>`).join('');
    svg.innerHTML=defs+grid+`<path class="chart-area" d="${area}"/><path class="chart-line" d="${d}"/>`+axes+dots;
    const line=svg.querySelector('.chart-line');if(line&&!reduceMotion){const len=line.getTotalLength();line.style.strokeDasharray=len;line.style.strokeDashoffset=len;requestAnimationFrame(()=>{line.style.transition='stroke-dashoffset 1.2s cubic-bezier(.2,.75,.25,1)';line.style.strokeDashoffset='0'});}
    const tip=chart.querySelector('.chart-tooltip');const show=(dot)=>{const i=Number(dot.dataset.i),rect=chart.getBoundingClientRect(),svgRect=svg.getBoundingClientRect();tip.textContent=`${labels[i]} · ${values[i]} consulta${values[i]===1?'':'s'}`;tip.style.left=((Number(dot.getAttribute('cx'))/W)*svgRect.width)+'px';tip.style.top=((Number(dot.getAttribute('cy'))/H)*svgRect.height)+'px';tip.classList.add('show');tip.setAttribute('aria-hidden','false')};
    svg.querySelectorAll('.chart-dot').forEach(dot=>{dot.addEventListener('mouseenter',()=>show(dot));dot.addEventListener('focus',()=>show(dot));dot.addEventListener('click',()=>show(dot));dot.addEventListener('mouseleave',()=>tip.classList.remove('show'));dot.addEventListener('blur',()=>tip.classList.remove('show'))});
  });
});
