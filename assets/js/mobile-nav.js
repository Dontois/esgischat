document.addEventListener('DOMContentLoaded', () => {
  const burger = document.querySelector('.topbar-burger');
  const sidebar = document.getElementById('sidebar-mobile');
  const backdrop = document.getElementById('sidebar-backdrop');
  const chatSidebar = document.querySelector('.chat-sidebar');

  if (!burger || !sidebar || !backdrop) return;

  const closeAll = () => {
    sidebar.classList.remove('actif');
    backdrop.classList.remove('actif');
    chatSidebar?.classList.remove('actif');
  };

  const toggleMenu = () => {
    const isOpen = sidebar.classList.contains('actif');
    sidebar.classList.toggle('actif', !isOpen);
    backdrop.classList.toggle('actif', !isOpen);
    if (isOpen) {
      chatSidebar?.classList.remove('actif');
    }
  };

  burger.addEventListener('click', toggleMenu);
  backdrop.addEventListener('click', closeAll);
  sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeAll));
});
