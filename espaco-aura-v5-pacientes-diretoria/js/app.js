document.addEventListener('DOMContentLoaded',()=>{
  const toast=(msg,type='info')=>{const old=document.querySelector('.aura-toast');if(old)old.remove();const t=document.createElement('div');t.className=`aura-toast ${type}`;t.innerHTML=`<div class="toast-icon">${type==='success'?'✓':type==='error'?'!':'i'}</div><div><strong>${type==='success'?'Tudo certo':type==='error'?'Atenção':'Espaço Aura'}</strong><p></p></div><button aria-label="Fechar">×</button>`;t.querySelector('p').textContent=msg;document.body.appendChild(t);requestAnimationFrame(()=>t.classList.add('show'));const close=()=>{t.classList.remove('show');setTimeout(()=>t.remove(),250)};t.querySelector('button').onclick=close;setTimeout(close,5000)};
  document.querySelectorAll('.alert[data-popup="1"]').forEach(el=>{toast(el.textContent.trim(),el.classList.contains('error')?'error':el.classList.contains('success')?'success':'info');el.remove()});
  document.querySelectorAll('[data-confirm]').forEach(el=>el.addEventListener('submit',e=>{if(!confirm(el.dataset.confirm||'Deseja continuar?'))e.preventDefault()}));
  const cargo=document.querySelector('#cargo'),vp=document.querySelector('#vinculoProf');if(cargo&&vp){const f=()=>vp.style.display=cargo.value==='profissional'?'block':'none';cargo.onchange=f;f()}
  if(window.AURA_AGENDAMENTO){
    const serv=document.querySelector('#servico'),prof=document.querySelector('#profissional'),data=document.querySelector('#data'),box=document.querySelector('#horarios'),btn=document.querySelector('#btnAgendar');
    const filtrar=()=>{const s=serv.value.toLowerCase();[...prof.options].forEach((o,i)=>{if(!i)return;o.hidden=!!s&&o.dataset.especialidade.toLowerCase()!==s});if(prof.selectedOptions[0]?.hidden)prof.value='';carregar()};
    const carregar=async()=>{btn.disabled=true;box.innerHTML='<p class="muted">Selecione profissional e data.</p>';if(!prof.value||!data.value)return;box.innerHTML='<p class="muted">Carregando horários...</p>';try{const r=await fetch(`api/horarios.php?profissional_id=${encodeURIComponent(prof.value)}&data=${encodeURIComponent(data.value)}`);const j=await r.json();if(!j.ok)throw new Error(j.mensagem);if(!j.horarios.length){box.innerHTML='<div class="inline-note">Nenhum horário disponível nesta data.</div>';return}box.innerHTML='<div class="horarios">'+j.horarios.map(h=>`<label class="horario-option ${h.ocupado?'ocupado':''}"><input type="radio" name="horario" value="${h.horario}" ${h.ocupado?'disabled':''} required><span>${h.horario}${h.ocupado?' · ocupado':''}</span></label>`).join('')+'</div>';box.querySelectorAll('input:not(:disabled)').forEach(i=>i.onchange=()=>btn.disabled=false)}catch(e){box.innerHTML='<div class="inline-note error">Não foi possível carregar os horários.</div>';toast(e.message||'Erro ao carregar horários','error')}};
    serv.addEventListener('change',filtrar);prof.addEventListener('change',carregar);data.addEventListener('change',carregar);
  }
});

document.addEventListener('DOMContentLoaded',()=>{
  const c=document.querySelector('[data-carousel]');
  if(c){const step=()=>Math.max(260,c.clientWidth*.72);document.querySelector('[data-carousel-prev]')?.addEventListener('click',()=>c.scrollBy({left:-step(),behavior:'smooth'}));document.querySelector('[data-carousel-next]')?.addEventListener('click',()=>c.scrollBy({left:step(),behavior:'smooth'}));}
});
