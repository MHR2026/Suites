// Reservas con el motor de Octorate dentro de la web:
// - los buscadores (form[data-booking]) envían a /<idioma>/busqueda/?octobooksearch=…,
//   el mismo formato que usa el widget oficial de Octorate;
// - en /busqueda/ el script de Octorate (widget/js/form.js) muestra ahí los resultados.
const lang = document.documentElement.lang || 'es';
const dma = (iso) => iso.split('-').reverse().join('/'); // 2026-11-10 → 10/11/2026
const iso = (d) => d.split('/').reverse().join('-'); // 10/11/2026 → 2026-11-10

document.querySelectorAll('form[data-booking]').forEach((form) => {
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const f = form.elements;
    const q = new URLSearchParams({
      siteKey: f.namedItem('siteKey').value,
      lang,
      ota: 'false',
      checkin: dma(f.namedItem('checkin').value),
      checkout: dma(f.namedItem('checkout').value),
      pax: f.namedItem('pax').value,
    });
    const ninos = f.namedItem('children');
    if (ninos && ninos.value !== '0') q.set('children', ninos.value);
    location.href = `/${lang}/busqueda/?octobooksearch=${encodeURIComponent(q.toString())}`;
  });
});

const pagina = document.querySelector('.busqueda');
if (pagina) {
  const busqueda = new URLSearchParams(location.search).get('octobooksearch');
  if (!busqueda) {
    pagina.querySelector('.busqueda-vacia').hidden = false;
  } else {
    const q = new URLSearchParams(busqueda);
    // el buscador de la página muestra la búsqueda actual
    const form = pagina.querySelector('form[data-booking]');
    for (const campo of ['checkin', 'checkout']) {
      if (q.get(campo)) form.elements.namedItem(campo).value = iso(q.get(campo));
    }
    for (const campo of ['pax', 'children']) {
      if (q.get(campo)) form.elements.namedItem(campo).value = q.get(campo);
    }
    // enlace de respaldo: el mismo motor a pantalla completa
    const alt = pagina.querySelector('.busqueda-alt');
    alt.querySelector('a').href = `https://book.octorate.com/octobook/site/reservation/result.xhtml?${busqueda}`;
    alt.hidden = false;
  }
}
