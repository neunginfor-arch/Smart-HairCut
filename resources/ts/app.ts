const toggle = document.querySelector<HTMLButtonElement>('[data-theme-toggle]');
toggle?.addEventListener('click', () => { document.documentElement.classList.toggle('dark'); localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light'; });
if (localStorage.theme === 'dark') document.documentElement.classList.add('dark');

document.title = document.title.replaceAll('Smart Cut', 'SM HAIR DESIGN');
const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
const brandNodes: Text[] = [];
while (walker.nextNode()) brandNodes.push(walker.currentNode as Text);
brandNodes.forEach((node) => {
  node.nodeValue = node.nodeValue
    ?.replaceAll('SMART CUT', 'SM HAIR DESIGN')
    .replaceAll('SMARTCUT', 'SM HAIR DESIGN')
    .replaceAll('Smart Cut', 'SM HAIR DESIGN') ?? null;
});

document.querySelectorAll<HTMLElement>('[data-toast]').forEach((toast) => setTimeout(() => toast.remove(), 4000));
