(function () {
    const base = document.body.dataset.base || '';
  const $ = (s, r = document) => r.querySelector(s);
    // mobile sidebar
    const burger = $('#burger'), side = $('#side');
  if (burger) burger.addEventListener('click', () => side.classList.toggle('open'));
    // flash messages fade out
    const al = $('.alert'); if (al) setTimeout(() => al.classList.add('gone'), 5000);
    // confirm dialogs: <button data-confirm="Are you sure?">
    document.addEventListener('click', e => {
        const c = e.target.closest('[data-confirm]');
    if (c && !confirm(c.dataset.confirm)) e.preventDefault();
    }
    );
    // AJAX live search (recipient/items.php)
    const f = $('#filters'), out = $('#results');
  if (f && out) {
        let t;
    const run = () => {
            const q = new URLSearchParams(new FormData(f)).toString();
      out.classList.add('loading');
      fetch(base + '/api/search.php?' + q).then(r => r.text()).then(h => {
                out.innerHTML = h; out.classList.remove('loading');
            }
            );
        }
        ;
    run();
    f.addEventListener('input', () => {
            clearTimeout(t); t = setTimeout(run, 250);
        }
        );
    f.addEventListener('submit', e => {
            e.preventDefault(); run();
        }
        );
    }
    // live notification badge (checks every 20s)
    const nb = $('#nbadge');
  if (document.querySelector('.app:not(.admin)')) setInterval(() => {
        fetch(base + '/api/notifications.php').then(r => r.json()).then(d => {
            let b = $('#nbadge'); const link = document.querySelector('a[href$="notifications.php"]');
      if (!link) return;
      if (d.unread > 0) {
                if (!b) {
                    b = document.createElement('b'); b.id = 'nbadge'; b.className = 'dot'; link.appendChild(b);
                }
                b.textContent = d.unread;
            }
            else if (b) b.remove();
        }
        ).catch(() => {
        }
        );
    }
    , 20000);
    // registration + password checks
    const reg = $('#regform');
  if (reg) reg.addEventListener('submit', e => {
        const p = reg.password.value, c = reg.confirm.value, m = $('#formerr');
    let msg = '';
    if (p.length < 8) msg = 'Password needs at least 8 characters.';
    else if (p !== c) msg = 'Passwords do not match.';
    if (msg) {
            e.preventDefault(); m.textContent = msg; m.hidden = false;
        }
    }
    );
  const role = $('#rolepick');
  if (role) role.addEventListener('click', e => {
        const b = e.target.closest('[data-role]'); if (!b) return;
    role.querySelectorAll('button').forEach(x => x.classList.toggle('on', x === b));
    $('#roleinput').value = b.dataset.role;
    }
    );
    // image preview on upload (donor/add_item.php)
    const file = $('#cover-input'), prev = $('#cover-prev');
  if (file && prev) file.addEventListener('change', () => {
        const fl = file.files[0]; if (fl) prev.innerHTML = '<img src="' + URL.createObjectURL(fl) + '" alt="">';
    }
    );
}
)();
(function() {
    const role=document.getElementById('roleinput'),box=document.getElementById('institution-fields');if(!role||!box)return;const sync=()=> {
        box.hidden=role.value!=='recipient';box.querySelectorAll('input,select,textarea').forEach(x=>x.disabled=box.hidden);
    }
    ;document.getElementById('rolepick').addEventListener('click',()=>setTimeout(sync,0));sync();
}
)();
