# 🗺️ World View Military GIS Module

Questo modulo funge da Single Page Application esportabile per la visualizzazione 3D militare dei metadati (satelliti, traffico aereo, attività sismica e CCTV MESH).

## 🚀 Architettura

- **Motore**: `react-globe.gl` e `three.js`.
- **Styling**: Tailwind CSS V4 con UI Post-Processing Shaders.
- **Dati live**:
  1. *Satelliti* (telemetria TLE live tramite CelesTrak, parsi con `satellite.js`).
  2. *Voli* (ADS-B via GeoJSON e OpenSky).
  3. *Sismico* (USGS Live Feed).
  4. *Visione Panoptic* (AI Bounding box tracking YOLO simulati tramite canvas layer PiP).

## 🛠 Compilazione
Il modulo è progettato per girare incapsulato nel sistema Host MCAG PHP. Build tramite Vite:

```bash
cd world-view
npm install
npm run build
```

La directory di output `/dist/` serve i file precompilati pronti all'ingestion.
I preset visivi NVG e FLIR sovrascrivono la scala emissiva dei materiali Three.js accoppiati ai filter in overlay DOM.

*Authored by Antigravity (Senior Full-Stack & Solutions Architect)*
