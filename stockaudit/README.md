# Stock Audit - Módulo de Auditoría de Stock para PrestaShop

## 📋 Descripción

Módulo completo para registrar y auditar **todos los movimientos de stock** de tus productos en PrestaShop.

**Compatible con PrestaShop 1.6, 1.7, 8 y 9**

### ✨ Características

- ✅ Registro automático de todos los cambios de stock
- ✅ Trazabilidad completa: cantidad anterior, nueva y diferencia
- ✅ Identifica el origen del cambio (pedido, backoffice, importación, etc.)
- ✅ Registra quién hizo el cambio (empleado o sistema)
- ✅ Estadísticas visuales en tiempo real
- ✅ Exportación a CSV/Excel
- ✅ Filtros avanzados por producto, fecha, tipo de movimiento, etc.
- ✅ Registro de IP y navegador para mayor seguridad
- ✅ Panel de administración intuitivo

---

## 🚀 Instalación

### Método 1: Manual

1. **Descarga el módulo** y descomprímelo
2. **Sube la carpeta `stockaudit`** completa a:
   ```
   /modules/stockaudit/
   ```
3. **Ve al backoffice** de PrestaShop
4. **Navega a:** Módulos → Módulos y Servicios
5. **Busca:** "Stock Audit"
6. **Haz clic en:** "Instalar"

### Método 2: Desde el Backoffice (ZIP)

1. **Comprime la carpeta** `stockaudit` en un archivo ZIP
2. **Ve al backoffice** de PrestaShop
3. **Navega a:** Módulos → Módulos y Servicios
4. **Haz clic en:** "Subir un módulo"
5. **Selecciona el archivo** `stockaudit.zip`
6. **Instala el módulo**

---

## 📊 Uso

### Acceder al Historial de Movimientos

Una vez instalado, aparecerá una nueva opción en tu menú:

**PrestaShop 1.6:**
```
Catálogo → Stock → Auditoría de Stock
```

**PrestaShop 1.7/8/9:**
```
Catálogo → Auditoría de Stock
```

### Panel de Administración

El panel muestra:

#### 📈 Estadísticas Resumidas
- **Total de movimientos** registrados
- **Incrementos** de stock (con total de unidades añadidas)
- **Decrementos** de stock (con total de unidades eliminadas)
- **Movimientos sin cambio**

#### 📋 Lista de Movimientos
Cada registro incluye:
- **Fecha y hora** exacta del movimiento
- **Producto** (con combinaciones si aplica)
- **Referencia** del producto
- **Stock anterior** y **stock nuevo**
- **Diferencia** (+/- unidades)
- **Tipo de movimiento** (pedido, manual, importación, etc.)
- **Origen** (BackOffice, FrontOffice, etc.)
- **Usuario** que realizó el cambio
- **ID del pedido** (si aplica)
- **IP** y **navegador** del usuario

### 🔍 Filtros Disponibles

Puedes filtrar por:
- **Fecha** (rango de fechas)
- **Producto** (nombre o referencia)
- **Tipo de movimiento**
- **Usuario/Empleado**
- **ID de pedido**

### 📤 Exportar a CSV/Excel

1. **Aplica los filtros** que necesites (opcional)
2. **Haz clic** en el botón "Exportar a CSV"
3. **Descarga el archivo** con todos los datos filtrados

El archivo CSV incluye:
- Todos los campos visibles en la lista
- Información adicional (motivo, IP, user agent)
- Compatible con Excel (UTF-8 con BOM)
- Delimitador: punto y coma (;)

---

## 🔧 Tipos de Movimientos Registrados

El módulo detecta automáticamente diferentes tipos de movimientos:

| Tipo | Descripción |
|------|-------------|
| **Pedido** | Reducción de stock por venta |
| **Validación de pedido** | Cuando se valida un nuevo pedido |
| **Actualización manual** | Cambio manual desde el backoffice |
| **Actualización** | Cambios genéricos de stock |
| **Importación** | Actualización mediante importación CSV |
| **Devolución** | Incremento por devolución de pedido |
| **Corrección** | Ajustes de inventario |

---

## 🗄️ Estructura de la Base de Datos

El módulo crea la tabla `ps_stock_audit` con los siguientes campos:

```sql
- id_stock_audit         → ID único del registro
- id_product             → ID del producto
- id_product_attribute   → ID de la combinación (0 si no aplica)
- id_order               → ID del pedido relacionado (si aplica)
- id_employee            → ID del empleado que hizo el cambio
- quantity_before        → Stock antes del cambio
- quantity_after         → Stock después del cambio
- quantity_diff          → Diferencia (+/-)
- movement_type          → Tipo de movimiento
- movement_source        → Origen (BackOffice, API, etc.)
- reason                 → Motivo/descripción del cambio
- date_add               → Fecha y hora del movimiento
- user_agent             → Navegador del usuario
- ip_address             → Dirección IP
```

---

## 🛠️ Configuración

El módulo funciona automáticamente sin configuración adicional.

Para acceder a la página de configuración:
1. Ve a **Módulos → Módulos y Servicios**
2. Busca **"Stock Audit"**
3. Haz clic en **"Configurar"**

---

## 🔒 Seguridad

El módulo incluye medidas de seguridad:

- ✅ Validación de datos con `pSQL()`
- ✅ Protección contra inyección SQL
- ✅ Registro de IP para auditoría
- ✅ Archivos `index.php` en todos los directorios
- ✅ Permisos solo para administradores

---

## 📝 Casos de Uso Comunes

### Investigar Pérdida de Stock
1. **Filtra por producto** que ha perdido stock
2. **Revisa la columna "Diferencia"** para ver decrementos
3. **Verifica el "Tipo"** y "Origen" del movimiento
4. **Identifica al usuario** responsable

### Auditoría Mensual
1. **Establece rango de fechas** del mes
2. **Exporta a CSV**
3. **Analiza en Excel** las tendencias

### Revisar Pedidos Específicos
1. **Filtra por ID de pedido**
2. **Verifica todos los productos** del pedido
3. **Comprueba si se descontó** correctamente

---

## ❓ Preguntas Frecuentes

### ¿El módulo registra cambios retroactivos?
**No.** El módulo solo registra cambios desde el momento de su instalación hacia adelante.

### ¿Afecta al rendimiento de la tienda?
**Mínimamente.** Los registros se hacen mediante hooks de PrestaShop de forma asíncrona.

### ¿Puedo eliminar registros antiguos?
**Sí.** Desde el panel puedes seleccionar y eliminar registros manualmente. Recomendamos exportar antes de eliminar.

### ¿Funciona con actualizaciones masivas?
**Sí.** El módulo captura cambios de:
- Importaciones CSV
- Actualizaciones masivas
- API/webservices
- Módulos de terceros (que usen hooks estándar)

### ¿Se puede recuperar stock con este módulo?
**No.** Es solo un módulo de **auditoría/registro**. No modifica stock, solo lo monitoriza.

---

## 🆘 Soporte

Si encuentras algún problema:

1. **Verifica** que el módulo esté correctamente instalado
2. **Comprueba** los logs de errores de PrestaShop
3. **Revisa** que los hooks estén activos
4. **Exporta** los datos antes de desinstalar

---

## 📄 Licencia

Este módulo es de código abierto y puede ser modificado según tus necesidades.

---

## 🔄 Actualizaciones Futuras

Posibles mejoras:
- [ ] Alertas automáticas por email
- [ ] Gráficos de tendencias
- [ ] Comparación entre períodos
- [ ] Exportación a PDF
- [ ] Filtro por almacén (multi-almacén)
- [ ] API REST para consultas

---

## 👨‍💻 Autor

**Tu Nombre**  
Versión: 1.0.0  
Fecha: 2025

---

## 📞 Contacto

Para consultas, sugerencias o reportar bugs, contacta con el desarrollador.

---

**¡Gracias por usar Stock Audit!** 🎉
