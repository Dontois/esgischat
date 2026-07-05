const API_BASE = 'api';
const AUTH_API = `${API_BASE}/auth.php`;
const SPA_API = `${API_BASE}/spa.php`;
const AMIS_API = `${API_BASE}/amis.php`;
const FLUX_API = `${API_BASE}/flux.php`;
const PROFIL_API = `${API_BASE}/profil.php`;

const toastZone = document.getElementById('zone-toast');
const appShell = document.getElementById('app-shell');
const authScreen = document.getElementById('auth-screen');
const appContent = document.getElementById('app-content');
let previewObjectUrl = null;

function getStoredUser() {
  try {
    return JSON.parse(sessionStorage.getItem('esgischat_user')) || null;
  } catch {
    return null;
  }
}

function setStoredUser(user) {
  if (user) {
    sessionStorage.setItem('esgischat_user', JSON.stringify(user));
  }
}

function getStoredToken() {
  return sessionStorage.getItem('esgischat_token') || '';
}

function setStoredToken(token) {
  if (token) {
    sessionStorage.setItem('esgischat_token', token);
  }
}

function clearStoredUser() {
  sessionStorage.removeItem('esgischat_user');
  sessionStorage.removeItem('esgischat_token');
}

// Sécurité : on s'authentifie avec un jeton secret généré par le serveur à la
// connexion (voir api/auth.php), jamais avec un simple identifiant utilisateur
// qui pourrait être modifié depuis les outils de développement du navigateur.
function authHeaders() {
  const token = getStoredToken();
  return token ? { 'X-Auth-Token': token } : {};
}

async function fetchJson(url, options = {}) {
  const headers = Object.assign({ Accept: 'application/json' }, options.headers || {}, authHeaders());
  const response = await fetch(url, Object.assign({}, options, { headers }));
  const data = await response.json().catch(() => null);
  return { status: response.status, ok: response.ok, data };
}

const TAILLE_MAX_IMAGE = 12 * 1024 * 1024; // 12 Mo, doit correspondre à inclure/fonctions.php

function updatePubImagePreview(input) {
  const preview = document.querySelector('[data-pub-preview]');
  const previewImg = document.querySelector('[data-pub-preview-img]');
  const previewName = document.querySelector('[data-pub-preview-name]');
  if (!preview || !previewImg || !previewName) return;

  const file = input.files?.[0];
  if (!file) {
    if (previewObjectUrl) {
      URL.revokeObjectURL(previewObjectUrl);
      previewObjectUrl = null;
    }
    previewImg.src = '';
    previewImg.alt = 'Aucune image sélectionnée';
    previewName.textContent = '';
    preview.classList.remove('visible');
    return;
  }

  if (file.size > TAILLE_MAX_IMAGE) {
    showToast('Image trop volumineuse (12 Mo maximum). Choisissez une image plus légère.', 'erreur');
    input.value = '';
    return;
  }

  if (previewObjectUrl) {
    URL.revokeObjectURL(previewObjectUrl);
  }
  previewObjectUrl = URL.createObjectURL(file);
  previewImg.src = previewObjectUrl;
  previewImg.alt = file.name;
  previewName.textContent = file.name;
  preview.classList.add('visible');
}

function clearPubImagePreview() {
  const input = document.getElementById('pub-image-input');
  if (input) {
    input.value = '';
  }
  updatePubImagePreview({ files: [] });
}


function showToast(message, type = 'succes') {
  if (!toastZone) return;
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.textContent = message;
  toastZone.appendChild(toast);
  window.setTimeout(() => toast.remove(), 3000);
}

function showAuthScreen() {
  authScreen.classList.remove('caché');
  appShell.classList.add('caché');
}

function showAppShell() {
  authScreen.classList.add('caché');
  appShell.classList.remove('caché');
}

function setActiveAuthTab(tab) {
  document.querySelectorAll('[data-auth-tab]').forEach((button) => {
    button.classList.toggle('actif', button.dataset.authTab === tab);
  });
}

function showAuthSection(section, token = '') {
  const messageBox = document.getElementById('auth-message');
  if (messageBox) {
    messageBox.innerHTML = '';
  }

  const subtitle = document.getElementById('auth-subtitle');
  if (subtitle) {
    subtitle.textContent = section === 'inscription'
      ? 'Créez votre compte ESGISchat pour rejoindre vos amis.'
      : section === 'forgot'
      ? 'Entrez votre adresse email pour recevoir un lien de réinitialisation.'
      : section === 'reset'
      ? 'Réinitialisez votre mot de passe pour retrouver l’accès.'
      : 'Connectez-vous ou créez votre compte ESGISchat';
  }

  ['connexion', 'inscription', 'forgot', 'reset'].forEach((name) => {
    const form = document.getElementById(`form-${name}`);
    if (!form) return;
    form.classList.toggle('caché', name !== section);
  });

  const tokenInput = document.getElementById('reset-token');
  if (tokenInput) {
    tokenInput.value = token;
  }

  if (section === 'connexion') {
    setActiveAuthTab('login');
  } else if (section === 'inscription') {
    setActiveAuthTab('inscription');
  } else {
    setActiveAuthTab('');
  }
}

function renderUserHeader(user) {
  const nameEl = document.getElementById('topbar-name');
  const avatarEl = document.getElementById('topbar-avatar');
  if (!nameEl || !avatarEl || !user) return;

  nameEl.textContent = `${user.prenom} ${user.nom}`;
  if (user.photo_profil) {
    avatarEl.innerHTML = `<img src="uploads/avatars/${user.photo_profil}" class="avatar avatar-sm" alt="">`;
  } else {
    const initials = `${String(user.prenom || '').charAt(0)}${String(user.nom || '').charAt(0)}`.toUpperCase();
    avatarEl.innerHTML = `<div class="avatar avatar-sm">${initials}</div>`;
  }
}

function parseHash() {
  const hash = window.location.hash.replace(/^#\/?/, '');
  const [route, query = ''] = hash.split('?');
  return { path: route || 'accueil', params: new URLSearchParams(query) };
}

function navigate(hash, replace = false) {
  if (replace) {
    window.location.replace(`${window.location.pathname}#/${hash}`);
  } else {
    window.location.hash = `#/${hash}`;
  }
}

function updateNavState(path) {
  document.querySelectorAll('[data-nav]').forEach((link) => {
    const target = link.dataset.nav;
    link.classList.toggle('actif', path === target || path.startsWith(`${target}/`));
  });
}

function showProfileTab(tab) {
  document.querySelectorAll('[data-profil-tab]').forEach((button) => {
    button.classList.toggle('actif', button.dataset.profilTab === tab);
  });

  const sectionInfos = document.getElementById('section-infos');
  const sectionMdp = document.getElementById('section-mdp');

  if (sectionInfos) {
    const visible = tab === 'infos';
    sectionInfos.classList.toggle('caché', !visible);
    sectionInfos.style.display = visible ? '' : 'none';
  }

  if (sectionMdp) {
    const visible = tab === 'mdp';
    sectionMdp.classList.toggle('caché', !visible);
    sectionMdp.style.display = visible ? '' : 'none';
  }
}

async function verifySession() {
  const result = await fetchJson(`${AUTH_API}?action=verify`, { method: 'GET' });
  if (result.ok && result.data?.success && result.data.user) {
    setStoredUser(result.data.user);
    renderUserHeader(result.data.user);
    return result.data.user;
  }
  clearStoredUser();
  return null;
}

async function loadView(path) {
  const [view, id] = path.split('/');
  const url = new URL(SPA_API, window.location.href);
  let route = view;
  const params = new URLSearchParams();

  if (view === 'amis') {
    route = 'amis';
    params.set('filtre', id === 'amis' ? 'amis' : 'tous');
  } else if (view === 'chat') {
    route = 'chat';
    if (id) params.set('avec', id);
  } else if (view === 'membre') {
    route = 'membre';
    if (id) params.set('id', id);
  } else if (view === 'profil') {
    route = 'profil';
  } else {
    route = 'accueil';
  }

  url.searchParams.set('view', route);
  for (const [key, value] of params.entries()) {
    url.searchParams.set(key, value);
  }

  const result = await fetchJson(url.href, { method: 'GET' });
  if (!result.ok || !result.data) {
    appContent.innerHTML = `<div class="empty-state">Impossible de charger la page.</div>`;
    return;
  }

  if (!result.data.success) {
    appContent.innerHTML = `<div class="empty-state">${result.data.message || 'Erreur de chargement.'}</div>`;
    return;
  }

  appContent.innerHTML = result.data.html || '';
  document.title = `${result.data.title || 'ESGISchat'} — ESGISchat`;
  updateNavState(path);
}

async function handleAuthRoute(path, params) {
  showAuthScreen();
  if (path === 'inscription') {
    showAuthSection('inscription');
    return;
  }
  if (path === 'forgot') {
    showAuthSection('forgot');
    return;
  }
  if (path === 'reset') {
    const token = params.get('token') || '';
    if (!token) {
      showAuthSection('reset');
      showToast('Jeton de réinitialisation manquant.', 'erreur');
      return;
    }
    const response = await fetchJson(`${AUTH_API}?action=reset&token=${encodeURIComponent(token)}`, { method: 'GET' });
    if (!response.ok || !response.data?.success) {
      showToast(response.data?.message || 'Lien de réinitialisation invalide.', 'erreur');
      showAuthSection('forgot');
      return;
    }
    showAuthSection('reset', token);
    return;
  }
  showAuthSection('connexion');
}

async function route() {
  const { path, params } = parseHash();
  const user = getStoredUser();
  const authPaths = ['login', 'inscription', 'forgot', 'reset'];

  if (!user) {
    if (!authPaths.includes(path)) {
      navigate('login', true);
      return;
    }
    await handleAuthRoute(path, params);
    return;
  }

  const verifiedUser = await verifySession();
  if (!verifiedUser) {
    navigate('login', true);
    return;
  }

  showAppShell();
  if (authPaths.includes(path)) {
    navigate('accueil', true);
    return;
  }

  await loadView(path || 'accueil');
}

async function handleAuthSubmit(event) {
  const form = event.target;
  const url = AUTH_API;
  const data = new FormData(form);

  if (form.id === 'form-connexion') {
    data.set('action', 'login');
  } else if (form.id === 'form-inscription') {
    data.set('action', 'register');
  } else if (form.id === 'form-forgot') {
    data.set('action', 'forgot');
  } else if (form.id === 'form-reset') {
    data.set('action', 'reset');
  } else {
    return;
  }

  const submitButton = form.querySelector('button[type="submit"]');
  if (submitButton) {
    submitButton.disabled = true;
    submitButton.textContent = 'Patientez...';
  }

  const messageBox = document.getElementById('auth-message');
  if (messageBox) {
    messageBox.innerHTML = '';
  }

  let result;
  try {
    result = await fetchJson(url, { method: 'POST', body: data });
  } catch (error) {
    result = null;
  }

  if (submitButton) {
    submitButton.disabled = false;
    submitButton.textContent = submitButton.dataset.defaultText || submitButton.textContent;
  }

  if (!result || !result.ok || !result.data) {
    showToast('Demande envoyée.', 'succes');
    return;
  }

  if (!result.data.success) {
    showToast(result.data.message || 'Erreur.', 'erreur');
    return;
  }

  if (form.id === 'form-connexion' || form.id === 'form-inscription') {
    setStoredToken(result.data.token);
    setStoredUser(result.data.user);
    renderUserHeader(result.data.user);
    navigate('accueil');
    showToast(result.data.message || 'Connexion réussie.');
    return;
  }

  if (form.id === 'form-forgot') {
    if (messageBox) {
      messageBox.innerHTML = `<div class="toast succes" style="margin:0 0 12px">${result.data.message || 'Si cet email existe dans notre base, un lien de réinitialisation a été envoyé.'}</div>`;
    } else {
      showToast(result.data.message || 'Si cet email existe dans notre base, un lien de réinitialisation a été envoyé.');
    }
    return;
  }

  if (form.id === 'form-reset') {
    showToast(result.data.message || 'Mot de passe modifié.');
    navigate('login');
  }
}

async function handleAppClick(event) {
  const target = event.target.closest('[data-nav], [data-auth-tab], [data-auth-nav], button[data-profil-tab], button[data-ajax-reaction], button[data-ami-envoyer], button[data-ami-repondre], button[data-ami-retirer], #btn-logout, #sidebar-logout');
  if (!target) return;

  if (target.matches('[data-nav]')) {
    event.preventDefault();
    navigate(target.dataset.nav);
    return;
  }

  if (target.matches('[data-auth-tab]')) {
    event.preventDefault();
    const route = target.dataset.authTab;
    navigate(route === 'login' ? 'login' : route);
    return;
  }

  if (target.matches('[data-auth-nav]')) {
    event.preventDefault();
    const route = target.dataset.authNav;
    navigate(route);
    return;
  }

  if (target.matches('[data-remove-pub-image]')) {
    event.preventDefault();
    clearPubImagePreview();
    return;
  }

  if (target.matches('#btn-logout, #sidebar-logout')) {
    event.preventDefault();
    // On invalide le jeton côté serveur avant de vider le stockage local,
    // pour qu'il ne puisse plus être réutilisé.
    fetchJson(`${AUTH_API}?action=logout`, { method: 'POST' }).finally(() => {
      clearStoredUser();
      navigate('login');
    });
    return;
  }

  if (target.matches('button[data-ajax-reaction]')) {
    event.preventDefault();
    const form = target.closest('form');
    if (!form) return;
    const publicationId = form.querySelector('input[name="publication_id"]').value;
    const type = form.querySelector('input[name="type"]').value;
    const resultBox = document.getElementById(`result-${publicationId}`);
    const formData = new FormData();
    formData.append('action', 'reagir');
    formData.append('publication_id', publicationId);
    formData.append('type', type);

    fetchJson(FLUX_API, { method: 'POST', body: formData }).then((result) => {
      if (!result.ok || !result.data?.success) {
        if (resultBox) resultBox.textContent = result.data?.message || 'Erreur';
        return;
      }
      const likeButton = document.querySelector(`[data-reaction-like="${publicationId}"]`);
      const dislikeButton = document.querySelector(`[data-reaction-dislike="${publicationId}"]`);
      const likeCount = document.querySelector(`[data-like-count="${publicationId}"]`);
      const dislikeCount = document.querySelector(`[data-dislike-count="${publicationId}"]`);
      if (likeButton) {
        likeButton.style.color = result.data.reaction === 'like' ? 'var(--like)' : '';
        likeButton.style.borderColor = result.data.reaction === 'like' ? 'var(--like)' : '';
      }
      if (dislikeButton) {
        dislikeButton.style.color = result.data.reaction === 'dislike' ? 'var(--dislike)' : '';
        dislikeButton.style.borderColor = result.data.reaction === 'dislike' ? 'var(--dislike)' : '';
      }
      if (likeCount) likeCount.textContent = result.data.like_count;
      if (dislikeCount) dislikeCount.textContent = result.data.dislike_count;
      if (resultBox) resultBox.textContent = '';
    });
    return;
  }

  if (target.matches('[data-profil-tab]')) {
    event.preventDefault();
    showProfileTab(target.dataset.profilTab);
    return;
  }

  if (target.matches('button[data-ami-envoyer], button[data-ami-repondre], button[data-ami-retirer]')) {
    event.preventDefault();
    const action = target.dataset.amiEnvoyer ? 'envoyer' : target.dataset.amiRepondre ? 'repondre' : 'retirer';
    const formData = new FormData();

    if (action === 'envoyer') {
      formData.append('action', 'envoyer');
      formData.append('cible_id', target.dataset.amiEnvoyer);
    } else if (action === 'repondre') {
      formData.append('action', 'repondre');
      formData.append('ami_id', target.dataset.amiRepondre);
      formData.append('statut', target.dataset.statut);
    } else if (action === 'retirer') {
      formData.append('action', 'retirer');
      formData.append('cible_id', target.dataset.amiRetirer);
    }

    fetchJson(AMIS_API, { method: 'POST', body: formData }).then((result) => {
      showToast(result.data?.message || 'Action envoyée.');
      if (result.data?.success) {
        const { path } = parseHash();
        loadView(path);
      }
    });
    return;
  }
}

async function handleAppSubmit(event) {
  const form = event.target;
  if (form.matches('#form-connexion, #form-inscription, #form-forgot, #form-reset')) {
    event.preventDefault();
    await handleAuthSubmit(event);
    return;
  }

  if (form.matches('[data-pub-form]')) {
    event.preventDefault();
    const formData = new FormData(form);
    formData.append('action', 'publier');
    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'Publication...';
    }

    const result = await fetchJson(FLUX_API, { method: 'POST', body: formData });
    if (submitButton) {
      submitButton.disabled = false;
      submitButton.innerHTML = '<i class="ph ph-paper-plane-right" aria-hidden="true"></i> Publier';
    }

    if (result.ok && result.data?.success && result.data.publication_html) {
      const container = document.getElementById('liste-flux');
      if (container) {
        container.insertAdjacentHTML('afterbegin', result.data.publication_html);
      }
      form.reset();
      clearPubImagePreview();
      showToast(result.data.message || 'Publication créée !');
    } else {
      showToast(result.data?.message || 'Impossible de publier. Réessayez.', 'erreur');
    }
    return;
  }

  if (form.matches('[data-comment-form]')) {
    event.preventDefault();
    const publicationId = form.querySelector('input[name="publication_id"]').value;
    const input = form.querySelector('input[name="contenu"]');
    const list = document.getElementById(`comments-${publicationId}`);
    const count = document.getElementById(`comment-count-${publicationId}`);
    const formData = new FormData();
    formData.append('action', 'commenter');
    formData.append('publication_id', publicationId);
    formData.append('contenu', input.value);

    const result = await fetchJson(FLUX_API, { method: 'POST', body: formData });
    if (result.ok && result.data?.success && list) {
      list.insertAdjacentHTML('beforeend', result.data.comment_html);
      if (count) {
        const label = result.data.comment_count > 1 ? 'commentaires' : 'commentaire';
        count.innerHTML = `<i class="ph ph-chat-circle" aria-hidden="true"></i> ${result.data.comment_count} ${label}`;
      }
      input.value = '';
      showToast(result.data.message || 'Commentaire ajouté.');
    }
    return;
  }

  if (form.matches('[data-profil-infos-form]') || form.matches('[data-profil-mdp-form]')) {
    event.preventDefault();
    const formData = new FormData(form);
    formData.append('action', form.matches('[data-profil-infos-form]') ? 'modifier_infos' : 'modifier_mdp');
    const result = await fetchJson(PROFIL_API, { method: 'POST', body: formData });
    if (result.ok && result.data?.success) {
      showToast(result.data.message || 'Mise à jour effectuée.');
      if (result.data.user) {
        setStoredUser(result.data.user);
        renderUserHeader(result.data.user);
      }
      if (result.data.token) {
        setStoredToken(result.data.token);
      }
      if (form.matches('[data-profil-mdp-form]')) {
        form.reset();
      }
    } else {
      showToast(result.data?.message || 'Erreur.', 'erreur');
    }
    return;
  }
}

function applyDefaultButtonText() {
  document.querySelectorAll('button[type="submit"]').forEach((button) => {
    if (!button.dataset.defaultText) {
      button.dataset.defaultText = button.textContent.trim();
    }
  });
}

window.addEventListener('DOMContentLoaded', () => {
  applyDefaultButtonText();
  document.body.addEventListener('click', handleAppClick);
  document.body.addEventListener('submit', handleAppSubmit);
  document.body.addEventListener('input', (event) => {
    const input = event.target.closest('[data-chat-search]');
    if (input) {
      const keyword = input.value.trim().toLowerCase();
      document.querySelectorAll('.chat-conv-item').forEach((item) => {
        const name = item.dataset.chatName || '';
        item.style.display = name.includes(keyword) ? '' : 'none';
      });
      return;
    }
  });

  document.body.addEventListener('change', (event) => {
    const input = event.target.closest('#pub-image-input');
    if (input) {
      updatePubImagePreview(input);
    }
  });

  window.addEventListener('hashchange', route);
  route();
});

