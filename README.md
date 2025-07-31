# MEF API - Consulta de inversiones públicas

API para consultar información de inversiones del Ministerio de Economía y Finanzas (MEF) de Perú.

## 🚀 URLs de la API

Una vez desplegado en Vercel, tu API estará disponible en:

```
https://tu-proyecto.vercel.app/api/mef
```

## 📡 Endpoints

### Consulta individual
```
GET /api/mef?id=2465550
```

### Consulta múltiple
```
GET /api/mef?ids=2465550,1234567,9876543
```

## 📋 Respuesta

### Exitosa
```json
{
  "success": true,
  "timestamp": "2025-01-31 15:30:00",
  "data": { ... },
  "formatted": {
    "proyecto": "Nombre del proyecto",
    "entidad": "MUNICIPALIDAD DISTRITAL DE ILABAYA",
    "funcion": "EDUCACIÓN",
    "costo_actualizado": 5921079.23,
    "devengado_acumulado": 5260390.62,
    "porcentaje_avance": 88.9,
    "codigo_unico": 2465550
  },
  "monday_format": "💰 PROYECTO: ...\n🏛️ ENTIDAD: ..."
}
```

### Error
```json
{
  "error": "Descripción del error",
  "debug": { ... }
}
```

## 🛠️ Desarrollo local

Para probar localmente:

1. Instalar PHP
2. Ejecutar servidor local:
   ```bash
   php -S localhost:8000
   ```
3. Probar endpoint:
   ```
   http://localhost:8000/api/mef.php?id=2465550
   ```

## 📝 Notas

- Máximo 10 códigos por consulta múltiple
- Timeout de 30 segundos por consulta
- Incluye pausa de 0.5s entre consultas múltiples para evitar sobrecarga