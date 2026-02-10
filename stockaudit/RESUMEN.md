# 📦 MÓDULO STOCK AUDIT - RESUMEN COMPLETO

## 🎯 ¿Qué hace este módulo?

Resuelve tu problema de **stock que desaparece** registrando **TODOS** los movimientos de stock automáticamente.

### Registro completo de:
- ✅ Ventas (pedidos)
- ✅ Cambios manuales desde backoffice
- ✅ Importaciones CSV
- ✅ Devoluciones
- ✅ Actualizaciones por API
- ✅ Cambios de otros módulos

---

## 📋 ¿Qué información registra?

Para cada movimiento de stock registra:

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Fecha y hora** | Momento exacto | 2025-02-10 15:30:45 |
| **Producto** | Nombre completo | Camiseta Roja Talla M |
| **Referencia** | SKU del producto | CAM-ROJA-M |
| **Stock anterior** | Cantidad antes | 25 |
| **Stock nuevo** | Cantidad después | 23 |
| **Diferencia** | Cambio (+/-) | -2 |
| **Tipo** | Qué causó el cambio | Pedido / Manual / Importación |
| **Origen** | Desde dónde | BackOffice / FrontOffice |
| **Usuario** | Quién lo hizo | Juan Pérez / Sistema |
| **ID Pedido** | Si fue una venta | #12345 |
| **IP** | Dirección IP | 192.168.1.1 |
| **Navegador** | User Agent | Chrome 120 / Safari |

---

## 🚀 INSTALACIÓN RÁPIDA

### Opción 1: Subir carpeta por FTP
```
1. Descarga la carpeta "stockaudit"
2. Súbela a: /modules/stockaudit/
3. Ve al backoffice → Módulos
4. Busca "Stock Audit"
5. Instala
```

### Opción 2: Subir ZIP desde backoffice
```
1. Usa el archivo stockaudit.zip
2. Backoffice → Módulos → Subir módulo
3. Selecciona stockaudit.zip
4. Instala
```

**¡Listo!** Ya está registrando movimientos.

---

## 📊 CÓMO USAR

### Ver historial de movimientos

**PrestaShop 1.6:**
```
Catálogo → Stock → Auditoría de Stock
```

**PrestaShop 1.7/8/9:**
```
Catálogo → Auditoría de Stock
```

### Panel de control

Verás:

1. **Estadísticas generales**
   - Total de movimientos
   - Incrementos de stock
   - Decrementos de stock
   - Sin cambios

2. **Lista completa de movimientos**
   - Con todos los datos
   - Ordenados por fecha (más recientes primero)

3. **Filtros**
   - Por producto
   - Por fecha
   - Por tipo de movimiento
   - Por usuario
   - Por pedido

### Exportar a Excel/CSV

```
1. (Opcional) Aplica filtros
2. Clic en "Exportar a CSV"
3. Se descarga el archivo
4. Ábrelo en Excel
```

El archivo incluye **TODA** la información, incluso campos que no se ven en pantalla.

---

## 🔍 CASOS DE USO PRÁCTICOS

### Caso 1: "Se me ha desaparecido stock del producto X"

```
1. Ve a Auditoría de Stock
2. Filtra por producto X
3. Ordena por fecha
4. Revisa la columna "Diferencia"
5. Busca valores negativos (decrementos)
6. Mira quién/qué lo causó
```

### Caso 2: "Quiero auditoría mensual"

```
1. Filtra por rango de fechas del mes
2. Exporta a CSV
3. Analiza en Excel
```

### Caso 3: "El pedido #12345 no descontó stock"

```
1. Filtra por ID Pedido: 12345
2. Verifica si aparecen los productos
3. Si no aparece → El stock NO se descontó
4. Si aparece → Revisa las cantidades
```

### Caso 4: "Alguien cambió stock y no sé quién fue"

```
1. Filtra por fecha aproximada
2. Mira la columna "Usuario"
3. Mira la columna "IP" para más detalles
```

---

## 📁 ARCHIVOS INCLUIDOS

```
stockaudit/
├── stockaudit.php                    → Archivo principal del módulo
├── config.xml                        → Configuración
├── install.sql                       → SQL para instalación manual
├── README.md                         → Documentación completa
├── INSTALACION.md                    → Guía de instalación paso a paso
├── TROUBLESHOOTING.md                → Solución de problemas
├── EJEMPLOS_PERSONALIZACION.php      → Código para personalizar
├── controllers/
│   └── admin/
│       └── AdminStockAuditController.php  → Panel administración
├── views/
│   └── templates/
│       └── admin/
│           └── stats.tpl             → Plantilla estadísticas
└── translations/
    └── es.php                        → Traducciones español
```

---

## ✅ COMPATIBILIDAD

| Versión PrestaShop | Compatible |
|-------------------|-----------|
| 1.6.x | ✅ SÍ |
| 1.7.x | ✅ SÍ |
| 8.x | ✅ SÍ |
| 9.x | ✅ SÍ |

**Requisitos mínimos:**
- PHP 5.6+ (recomendado PHP 7.4+)
- MySQL 5.6+ (recomendado MySQL 8.0+)

---

## 🛡️ SEGURIDAD

- ✅ Protección contra inyección SQL
- ✅ Validación de datos con pSQL()
- ✅ Solo accesible por administradores
- ✅ Registro de IP para auditoría
- ✅ Archivos index.php en todos los directorios

---

## ❓ PREGUNTAS FRECUENTES

### ¿Registra movimientos pasados?
**No.** Solo desde el momento de instalación.

### ¿Afecta al rendimiento?
**Mínimamente.** Los registros son muy ligeros.

### ¿Ocupa mucho espacio?
Un registro ocupa ~300 bytes. 
10,000 movimientos = ~3 MB

### ¿Puedo borrar registros antiguos?
**Sí.** Desde el panel o con SQL. 
Recomendamos exportar primero.

### ¿Funciona con multi-tienda?
**Sí.** Registra movimientos de todas las tiendas.

### ¿Puedo personalizarlo?
**Sí.** Es código abierto. 
Ver archivo EJEMPLOS_PERSONALIZACION.php

---

## 🔧 TROUBLESHOOTING RÁPIDO

### El módulo no aparece
```
→ Verifica ruta: /modules/stockaudit/
→ Limpia caché
→ Revisa permisos (755/644)
```

### No registra movimientos
```
→ Verifica hooks en base de datos
→ Reinstala el módulo
→ Activa modo debug
```

### No aparece el menú
```
→ Verifica tabla ps_tab
→ Limpia permisos de empleados
→ Reinstala
```

### Exportación no funciona
```
→ Verifica permisos /tmp/
→ Revisa headers PHP
→ Comprueba límite de memoria
```

**Ver TROUBLESHOOTING.md para guía completa**

---

## 📞 SOPORTE

1. **Lee primero:**
   - README.md → Documentación completa
   - INSTALACION.md → Guía paso a paso
   - TROUBLESHOOTING.md → Solución de problemas

2. **Si sigues con problemas:**
   - Activa modo debug
   - Revisa logs de PrestaShop
   - Anota el error exacto
   - Verifica versión PHP/MySQL

---

## 🎉 VENTAJAS DEL MÓDULO

✅ **Trazabilidad total** - Nunca más perderás stock sin saber por qué
✅ **Fácil de usar** - Interface intuitiva, sin configuración compleja
✅ **Exportación** - Lleva tus datos a Excel para análisis
✅ **Multiversion** - Funciona en PS 1.6, 1.7, 8 y 9
✅ **Seguro** - Registro de IP y usuario para auditoría
✅ **Eficiente** - No afecta rendimiento de tu tienda
✅ **Código abierto** - Personalizable según necesites
✅ **Documentado** - Guías completas incluidas

---

## 🚀 PRÓXIMOS PASOS

1. **Instala el módulo**
2. **Realiza una venta de prueba**
3. **Verifica que se registró**
4. **Familiarízate con los filtros**
5. **Prueba la exportación**
6. **¡Disfruta de la trazabilidad total!**

---

## 📝 NOTAS IMPORTANTES

- **Backup:** Siempre haz backup antes de instalar módulos
- **Pruebas:** Prueba primero en entorno de desarrollo
- **Histórico:** Solo registra desde instalación, no retroactivo
- **Espacio:** Limpia registros antiguos periódicamente
- **Privacidad:** Los datos incluyen IPs de usuarios

---

## 📊 EJEMPLO DE FLUJO DE TRABAJO

```
1. Cliente hace pedido
   ↓
2. Stock se descuenta automáticamente
   ↓
3. Módulo registra:
   - Producto: Camiseta Roja M
   - Stock anterior: 10
   - Stock nuevo: 9
   - Diferencia: -1
   - Tipo: Pedido
   - Pedido: #12345
   - Fecha: 2025-02-10 15:30:45
   ↓
4. Tú puedes verlo en cualquier momento
   ↓
5. Exportar y analizar tendencias
```

---

## 🎓 RECURSOS ADICIONALES

- **Documentación oficial PrestaShop:**
  https://devdocs.prestashop.com/

- **Hooks disponibles:**
  https://devdocs.prestashop.com/1.7/modules/concepts/hooks/

- **Base de datos PrestaShop:**
  https://devdocs.prestashop.com/1.7/development/database/

---

## 📄 LICENCIA

Código abierto - Libre para usar y modificar

---

## ✨ VERSIÓN

**v1.0.0** - Febrero 2025

---

## 🙏 CRÉDITOS

Desarrollado para solucionar problemas reales de gestión de stock en tiendas PrestaShop.

---

**¡Gracias por usar Stock Audit!**

Si te ha sido útil, considera:
- ⭐ Valorar positivamente
- 💬 Compartir feedback
- 🐛 Reportar bugs
- 💡 Sugerir mejoras

---

**¿Listo para empezar?** 

→ Instala el módulo
→ Nunca más pierdas stock sin saber por qué
→ Toma decisiones basadas en datos reales

🚀 **¡A por ello!**
