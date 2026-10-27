// Ofertas de la portada:
// - cada oferta se oculta sola después de su fecha `data-hasta` (y la sección entera si no queda ninguna);
// - el botón de la oferta rellena las fechas del buscador de reservas (`data-llegada`) y baja hasta él.
const iso = (d) => d.toISOString().slice(0, 10);
const hoy = iso(new Date());

document.querySelectorAll('[data-oferta]').forEach((oferta) => {
  if (oferta.dataset.hasta && hoy > oferta.dataset.hasta) oferta.remove();
});
document.querySelectorAll('.ofertas').forEach((seccion) => {
  if (!seccion.querySelector('[data-oferta]')) {
    seccion.remove();
    document.querySelectorAll('a[href$="#ofertas"]').forEach((a) => a.remove());
  }
});

document.querySelectorAll('[data-oferta] .oferta-btn').forEach((btn) => {
  btn.addEventListener('click', () => {
    const llegada = btn.closest('[data-oferta]').dataset.llegada;
    const form = document.querySelector('#reservar form[data-booking]');
    if (!llegada || !form) return;
    const manana = new Date();
    manana.setDate(manana.getDate() + 1);
    const checkin = form.elements.namedItem('checkin');
    const checkout = form.elements.namedItem('checkout');
    const inicio = new Date(llegada > iso(manana) ? llegada : iso(manana));
    const fin = new Date(inicio);
    fin.setDate(inicio.getDate() + (Number(form.dataset.min) || 1));
    checkin.value = iso(inicio);
    checkout.value = iso(fin);
    checkin.dispatchEvent(new Event('change'));
  });
});
