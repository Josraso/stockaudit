# 🚀 GUÍA RÁPIDA DE INSTALACIÓN

## Paso 1: Subir el módulo

1. Descarga la carpeta completa `stockaudit`
2. Súbela a tu servidor PrestaShop en:
   ```
   /modules/stockaudit/
   ```
   (Asegúrate de que la carpeta se llama exactamente `stockaudit`)

## Paso 2: Verificar permisos

Asegúrate de que la carpeta tiene los permisos correctos:
```bash
chmod 755 /tu-ruta-prestashop/modules/stockaudit
chmod 644 /tu-ruta-prestashop/modules/stockaudit/*.php
```

## Paso 3: Instalar desde el backoffice

1. Accede al **backoffice** de tu PrestaShop
2. Ve a: **Módulos → Módulos y Servicios**
3. Busca: **"Stock Audit"** o **"Auditoría"**
4. Haz clic en **"Instalar"**
5. Confirma la instalación

## Paso 4: Verificar instalación

Después de instalar, verifica que:

✅ Aparece una nueva opción en el menú:
   - **PS 1.6:** Catálogo → Stock → Auditoría de Stock
   - **PS 1.7/8/9:** Catálogo → Auditoría de Stock

✅ Se ha creado la tabla en la base de datos:
   - Tabla: `ps_stock_audit` (o con tu prefijo personalizado)

## Paso 5: Primer uso

1. Ve a **Auditoría de Stock** en el menú
2. Verás el panel con estadísticas (probablemente vacías al inicio)
3. Realiza una **venta de prueba** o **modifica stock manualmente**
4. **Recarga la página** de Auditoría de Stock
5. ¡Deberías ver tu primer registro! 🎉

---

## 🔍 ¿No aparece el módulo?

### Solución 1: Limpiar caché
```bash
# Por FTP o SSH:
rm -rf var/cache/*
# o
rm -rf cache/*
```

### Solución 2: Regenerar clases
En el backoffice:
1. Ve a: **Parámetros Avanzados → Rendimiento**
2. Haz clic en: **"Limpiar caché"**
3. Activa: **"Forzar compilación"** (temporalmente)

### Solución 3: Verificar archivos
Comprueba que existen estos archivos:
```
/modules/stockaudit/stockaudit.php
/modules/stockaudit/config.xml
/modules/stockaudit/controllers/admin/AdminStockAuditController.php
```

---

## 📊 Primer Test

Para probar que funciona correctamente:

1. Ve a **Catálogo → Productos**
2. Edita cualquier producto
3. Cambia la **cantidad en stock**
4. Guarda el producto
5. Ve a **Auditoría de Stock**
6. ¡Deberías ver el cambio registrado!

---

## ❓ Problemas Comunes

### "Error al crear la tabla"
- Verifica que tu usuario MySQL tiene permisos `CREATE TABLE`
- Comprueba los logs de errores en: `/var/logs/` o `/logs/`

### "No se registran movimientos"
- Verifica que los hooks están activos:
  - Ve a: **Módulos → Posiciones**
  - Busca: "Stock Audit"
  - Debe aparecer en varios hooks

### "No puedo exportar a CSV"
- Verifica permisos de escritura en `/tmp/`
- Comprueba que `php.ini` permite descargas

---

## 🎯 ¡Listo!

Ya puedes empezar a **rastrear todos los movimientos** de stock de tu tienda.

**Recuerda:** El módulo solo registra movimientos desde el momento de su instalación. No hay datos históricos previos.

---

**¿Necesitas ayuda?** Revisa el README.md completo para más detalles.
