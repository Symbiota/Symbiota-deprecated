<?php
/*
------------------
Language: Spanish (Español)
------------------
*/
$LANG['TAXA_NAME'] = 'Cargador taxonómico por lotes';
$LANG['TAXA_ADMIN'] = 'Esta página le permite a un administrador taxonómico cargar por lotes los archivos de datos taxonómicos. Consulte las páginas de la';
$LANG['SYM_DOC'] = 'Documentación de Symbiota';
$LANG['PAGES_DETAILS'] = 'para obtener más detalles sobre el diseño del Tesauro Taxonómico.';

$LANG['SOURCE_FIELD'] = 'Campo de origen';
$LANG['TARGET_FIELD'] = 'Campo de destino';

$LANG['VERIFY_FIELD'] = '* Los campos en amarillo aún no se han verificado';

$LANG['TAXA_UP'] = 'Formulario de carga de taxa';
$LANG['FULL_FILE'] = 'Ruta de archivo completa:';
$LANG['FLAT_STRUCT'] = 'Aquí se pueden cargar archivos de texto planos estructurados, delimitados por comas (CSV). El nombre científico es el único campo requerido por debajo del rango de género. Sin embargo, familia, autor y rankid (como se define en la tabla de taxonunits) siempre se recomiendan. Para los taxones de nivel superior, los padres y los rangos deben incluirse para construir la jerarquía taxonómica. Los archivos de datos grandes se pueden comprimir como un archivo ZIP antes de importarlos. Si el paso de carga del archivo falla sin mostrar un mensaje de error, es posible que el tamaño del archivo exceda los límites de carga del archivo establecidos dentro de su instalación de PHP (consulte el archivo de configuración de PHP).';

$LANG['UPLOAD_FILE'] = 'Subir archivo:';
$LANG['OPTION_MAN'] = '* Esta opción es para la carga manual de un archivo de datos. Introduzca la ruta completa al archivo de datos ubicado en el servidor de trabajo.';

$LANG['TOGGLE_MAN'] = 'Alternar la opción de carga manual';

$LANG['UPLOAD_ITIS'] = 'Archivo de carga de ITIS';
$LANG['ITIS_DATA'] = 'El extracto de datos';
$LANG['ITIS_DOWNLOAD'] = 'de la página de descarga de ITIS';
$LANG['CAN_UPLOADED'] = 'se puede cargar usando esta función. Tenga en cuenta que el archivo debe estar en su formato delimitado por tuberías de un solo archivo (ejemplo:';
$LANG['FILE_BIN'] = 'CyprinidaeItisExample.bin';

$LANG['LEGEND'] = ' El archivo puede tener la extensión .csv, aunque No esté delimitado por comas. No se garantiza que esta opción de carga funcione si el formato de descarga de ITIS cambia con frecuencia. Los archivos de datos grandes se pueden comprimir como un archivo ZIP antes de importarlos. Si el paso de carga del archivo falla sin mostrar un mensaje de error, es posible que el tamaño del archivo exceda los límites de carga del archivo establecidos dentro de su instalación de PHP (consulte el archivo de configuración de PHP). Si se incluyen sinónimos y vernáculos, estos datos también se incorporarán en el proceso de carga.';

$LANG['FILE_NOT'] = 'No se eligió ningún archivo';
$LANG['TARGET_THES'] = 'Tesauro de destino:';

$LANG['CLEAN_ANA'] = 'Limpiar y analizar';
$LANG['LEGEND2'] = 'Si la información de los taxones se cargó en la tabla UploadTaxa utilizando otros medios, se puede usar este formulario para limpiar y analizar los nombres de los taxones en preparación para cargarlos en las tablas taxonómicas (taxa, taxstatus).';

/* Agregado por jt */
$LANG['TAXA_LOADER'] = 'Cargador de Taxa';
$LANG['HOME'] = 'Inicio';
$LANG['TAXONOMIC_TREE_VIEWER'] = 'Visor de árbol taxonómico';
$LANG['TAXA_BATCH_LOADER'] = 'Carga de lotes de taxa';
$LANG['FIELD_UNMAPPED'] = 'Campo sin mapear';
$LANG['LEAVE_FIELD_UNMAPPED'] = 'Dejar campo sin asignar';
$LANG['TRANSFER_TAXA_TO_CENTRAL_TABLE'] = 'Transferencia de taxones a la mesa central';
$LANG['REVIEW_UPLOAD_STATISTICS'] = 'Revise las estadísticas de carga a continuación antes de activar. Use la opción de descarga para revisar y / o ajustar para recargar si es necesario.';
$LANG['TAXA_UPLOADED'] = 'Taxa uploaded';
$LANG['TOTAL_TAXA'] = 'Taxa total';
$LANG['INCLUDES_NEW_PARENT_TAXA'] = 'incluye nuevos taxa padres';
$LANG['TAXA_ALREADY'] = 'Taxa ya en tesauro.';
$LANG['NEW_TAXA'] = 'Nuevos taxones';
$LANG['ACCEPTED_TAXA'] = 'Taxones aceptados';
$LANG['NON_ACCEPTED'] = 'Taxones no aceptados';
$LANG['PROBLEMATIC_TAXA'] = 'Taxones problematicos';
$LANG['THESE_TAXA_ARE_MARKED_AS_FAILED'] = 'Estos taxones están marcados como FALLIDOS dentro del campo de notas y no se cargarán hasta que se hayan resuelto los problemas. Es posible que desee descargar los datos (enlace a continuación), corregir las malas relaciones y luego volver a cargar.';
$LANG['UPLOAD_STATISTICS_ARE_UNAVAILABLE'] = 'Las estadísticas de carga no están disponibles';
$LANG['DOWNLOAD_CSV_TAXA_FILE'] = 'Descargar archivo taxa CSV';
$LANG['MAP_INPUT_FILE'] = 'Archivo de entrada del mapa';
$LANG['UPLOAD_ITIS_FILE'] = 'Subir archivo ITIS';
$LANG['ANALYZE_TAXA'] = 'Analizar taxa';

?>