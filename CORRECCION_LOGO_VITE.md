# Corrección del logo EMPACAR en Vite

Se corrigió el error:

```txt
[plugin:vite:import-analysis] Failed to resolve import "/img/empacar-brand.svg" from "resources/js/Pages/Landing.vue"
```

## Causa

Vite intentaba resolver `/img/empacar-brand.svg` como import interno del paquete al estar escrito como `src="/img/empacar-brand.svg"` dentro de componentes Vue.

## Solución aplicada

- Se creó el archivo público `public/img/empacar-brand.svg`.
- Se cambió el uso del logo a binding dinámico `:src="empacarLogo"` en:
  - `resources/js/Pages/Landing.vue`
  - `resources/js/Pages/Auth/Login.vue`
  - `resources/js/Pages/Login.vue`
- Se actualizó el fallback del modelo `Empresa` para usar `asset('img/empacar-brand.svg')`.

Después de reemplazar los archivos, ejecutar:

```bash
php artisan optimize:clear
npm run dev
```

o para producción:

```bash
npm run build
```
