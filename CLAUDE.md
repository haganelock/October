# CLAUDE.md — Sito Hagane

Guida per Claude (e per chiunque lavori al repo). Leggere prima di toccare i file.

## Cos'è questo repository

Contiene **due cose ben distinte**:

1. **`public/`** → il **sito web statico di Hagane** (lucchetto keyless per bici/scooter/moto del brand **HGN S.r.l.**). **È il sito da pubblicare.**
2. **`OCTOBER_SHIT/`** → il vecchio progetto **October CMS**, tenuto solo come **archivio**.

Contesto: il sito era nato su **October CMS**, ma mancavano dei pezzi (in particolare carrello/checkout e-commerce). October è stato quindi **abbandonato** in favore di un **sito statico** con i pagamenti gestiti tramite **link di pagamento Stripe**.

## ⭐ `public/` = il sito da pubblicare su Firebase

**`public/` è il sito statico da mettere online su Firebase Hosting.** È questa la cartella che va in produzione: contiene tutte le pagine `.html` e gli `assets/` (CSS, JS, immagini, font). Nel deploy Firebase la *public directory* è esattamente `public/`.

> ⚠️ Tutto lo sviluppo del sito si fa **dentro `public/`**.

## `OCTOBER_SHIT/` = archivio (solo risorse grafiche)

È il **vecchio progetto October CMS**, tenuto qui **solo come magazzino** nel caso serva recuperare qualche **risorsa grafica** (immagini, video, loghi del tema `themes/hagane`).

- **NON** è il sito in produzione, **NON** va pubblicato su Firebase, **NON** va sviluppato.
- Contiene `OCTOBER_SHIT/auth.json` con credenziali del marketplace October: è **escluso dal versionamento** tramite `.gitignore`. Non committarlo.

## Com'è fatto il sito (`public/`)

Sito **statico** puro: HTML + CSS + JS, nessun backend.

**Pagine** (header e footer sono scritti *inline* in ogni pagina):
- `index.html` — home (hero, "come funziona", "perché Hagane", modelli + acquisto)
- `products.html` — pagina acquisto
- `specs.html` — specifiche tecniche
- `faq.html` — domande frequenti
- `privacy.html`, `cookie.html`, `condizioni.html` — pagine legali (GDPR / condizioni di vendita)

**Cosa carica davvero ogni pagina:** soltanto **`assets/css/hagane.css`** e **`assets/js/site.js`**.

- `site.js` = script custom: i18n IT/EN, menu mobile, animazioni "reveal" allo scroll, fallback vari.
- **Bilingue IT/EN:** l'italiano è il testo di default nel DOM; l'inglese arriva dal dizionario dentro `site.js` (attributi `data-i18n` / `data-i18n-html`, switch lingua `IT/EN` in alto).

**Pulizia già fatta:** gli asset residui del vecchio tema October (`vendor.js` ~1,3 MB, `components.js`, jQuery, `ScrollTrigger`, `ScrollToPlugin`, `main.css`, `vendor.css`, `style.css`, e i partial `header.htm`/`footer.htm`) **sono stati rimossi** perché non usati da nessuna pagina. Restano solo `assets/js/site.js` e `assets/css/hagane.css`.

## Pagamenti (Stripe)

Il checkout è gestito con **link di pagamento Stripe** (niente carrello). I bottoni "Acquista ora" sono in `index.html` e `products.html`:

| Modello | Specifiche | Prezzo | Link Stripe |
|---|---|---|---|
| **Hagane 3414** | 14 mm, 1,15 kg — per la bici | **€99** (da €198) | `https://buy.stripe.com/9B600kbZ5fTeeO4cKj3wQ06` |
| **Hagane 4218** | 18 mm, 2,05 kg — per scooter e moto | **€149** (da €298) | `https://buy.stripe.com/28EdRa9QXgXi8pGeSr3wQ05` |

Per cambiare prezzi/prodotti: aggiornare il link Stripe e il prezzo mostrato in quelle due pagine.

## Dati societari

HGN S.r.l. — Corso Lodi 18, 20135 Milano (MI), Italia — P.IVA / C.F. 11577150961 — PEC `hgn@pec.it` — email `info@hagane.it`.

## Deploy su Firebase Hosting

**Configurato.** Project Firebase: **`hagane-e15f3`** (in `.firebaserc`). La *public directory* è `public/` (in `firebase.json`).

Per pubblicare, dal proprio computer (serve la Firebase CLI installata e `firebase login` fatto una volta), dalla radice del repo:

```bash
firebase deploy --only hosting
```

Anteprima locale prima di pubblicare: `firebase emulators:start --only hosting` (oppure un qualsiasi server statico nella cartella `public/`).

## Note operative per Claude

- Modificare **solo `public/`** per qualsiasi cosa riguardi il sito. `OCTOBER_SHIT/` si apre solo per pescare risorse grafiche.
- I file nascosti alla radice del repo (`.babelrc`, `.editorconfig`, `.htaccess`, `.github/`, `.jshintrc`, `.env.example`…) sono residui di October: ignorabili ed eventualmente eliminabili.
- Non committare credenziali (`auth.json`, `.env`).
