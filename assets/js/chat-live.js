// getStoredUser, authHeaders, fetchJson et showToast sont déjà définis dans app.js
// (chargé avant ce fichier). On les réutilise directement, sans les redéclarer,
// pour éviter une erreur "already declared" qui bloquerait tout ce script.

let chatPreviewUrl = null;

const renderMessages = (messages, currentUserId) => {
  const messagesBox = document.querySelector('[data-chat-messages]');
  if (!messagesBox) return;

  messagesBox.innerHTML = '';

  if (!messages.length) {
    messagesBox.innerHTML = '<p style="text-align:center;color:var(--texte-3)">Aucun message. Dites bonjour !</p>';
    return;
  }

  messages.forEach((message) => {
    const mine = Number(message.expediteur_id) === Number(currentUserId);
    const bubble = document.createElement('div');
    bubble.className = `message-bulle ${mine ? 'moi' : 'autre'}`;
    const parts = [];
    if (message.contenu) {
      parts.push(`<div class="message-contenu">${(message.contenu || '').replace(/\n/g, '<br>')}</div>`);
    }
    if (message.image_url) {
      parts.push(`<img src="${message.image_url}" class="message-image ${mine ? 'moi' : 'autre'}" alt="Pièce jointe">`);
    }
    parts.push(`<div class="message-heure">${message.date_label || ''}</div>`);
    bubble.innerHTML = parts.join('');
    messagesBox.appendChild(bubble);
  });
};

const getActiveConversationId = () => {
  const form = document.querySelector('[data-chat-form]');
  return form?.querySelector('input[name="destinataire_id"]')?.value || '';
};

const TAILLE_MAX_IMAGE_CHAT = 12 * 1024 * 1024; // 12 Mo, doit correspondre à inclure/fonctions.php

const updateChatImagePreview = (input) => {
  const preview = document.querySelector('[data-chat-preview]');
  const previewImg = document.querySelector('[data-chat-preview-img]');
  const previewName = document.querySelector('[data-chat-preview-name]');
  if (!preview || !previewImg || !previewName) return;

  const file = input.files?.[0];
  if (!file) {
    if (chatPreviewUrl) {
      URL.revokeObjectURL(chatPreviewUrl);
      chatPreviewUrl = null;
    }
    previewImg.src = '';
    previewImg.alt = 'Aucune image sélectionnée';
    previewName.textContent = '';
    preview.classList.add('caché');
    return;
  }

  if (file.size > TAILLE_MAX_IMAGE_CHAT) {
    showToast('Image trop volumineuse (12 Mo maximum). Choisissez une image plus légère.', 'erreur');
    input.value = '';
    return;
  }

  if (chatPreviewUrl) {
    URL.revokeObjectURL(chatPreviewUrl);
  }
  chatPreviewUrl = URL.createObjectURL(file);
  previewImg.src = chatPreviewUrl;
  previewImg.alt = file.name;
  previewName.textContent = file.name;
  preview.classList.remove('caché');
};

const clearChatImagePreview = () => {
  const input = document.querySelector('[data-chat-image]');
  if (input) {
    input.value = '';
  }
  updateChatImagePreview({ files: [] });
};

const isUserTyping = () => {
  const form = document.querySelector('[data-chat-form]');
  const textarea = form?.querySelector('[data-chat-input]');
  return !!(textarea && (document.activeElement === textarea || textarea.value.trim().length > 0));
};

const refreshCurrentConversation = async () => {
  if (isUserTyping()) return;

  const interlocuteurId = getActiveConversationId();
  const messagesBox = document.querySelector('[data-chat-messages]');
  if (!interlocuteurId || !messagesBox) return;

  const url = new URL('api/chat.php', window.location.href);
  url.searchParams.set('action', 'charger');
  url.searchParams.set('avec', interlocuteurId);
  const response = await fetchJson(url.href, { method: 'GET' });
  if (response.ok && response.data?.success) {
    renderMessages(response.data.messages || [], getStoredUser()?.id || '');
    messagesBox.scrollTop = messagesBox.scrollHeight;
  }
};

window.addEventListener('DOMContentLoaded', () => {
  document.body.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!form.matches('[data-chat-form]')) return;
    event.preventDefault();
    const input = form.querySelector('[data-chat-input]');
    const fileInput = form.querySelector('[data-chat-image]');
    const interlocuteurId = form.querySelector('input[name="destinataire_id"]')?.value || '';
    const contenu = input?.value.trim();
    const imageFile = fileInput?.files?.[0];
    if (!contenu && !imageFile) return;

    const formData = new FormData();
    formData.append('action', 'envoyer');
    formData.append('destinataire_id', interlocuteurId);
    if (contenu) formData.append('contenu', contenu);
    if (imageFile) formData.append('image', imageFile);

    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'Envoi...';
    }

    const result = await fetchJson('api/chat.php', { method: 'POST', body: formData });
    if (submitButton) {
      submitButton.disabled = false;
      submitButton.textContent = 'Envoyer';
    }

    if (!result.ok || !result.data?.success) {
      showToast(result.data?.message || 'Impossible d\'envoyer le message.', 'erreur');
      return;
    }

    if (input) input.value = '';
    if (fileInput) fileInput.value = '';
    clearChatImagePreview();
    await refreshCurrentConversation();
  });

  document.body.addEventListener('change', (event) => {
    const input = event.target.closest('[data-chat-image]');
    if (input) {
      updateChatImagePreview(input);
    }
  });

  document.body.addEventListener('click', (event) => {
    if (event.target.closest('[data-chat-remove-image]')) {
      event.preventDefault();
      clearChatImagePreview();
    }
  });

  setInterval(refreshCurrentConversation, 4000);
});
