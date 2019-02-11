<?php
/*
------------------
Language: ESPAÑOL
------------------
*/
$LANG['A'] = 'Procesador de imágenes';
$LANG['B'] = 'Estas herramientas están diseñadas para ayudar a los gestores de recogida en imágenes de muestras de procesamiento por lotes. 
				Póngase en contacto con el administrador del portal para ayudar a configurar un nuevo flujo de trabajo. 
				Una vez que se establece un perfil, el administrador de la colección puede utilizar este formulario para desencadenar manualmente el procesamiento de imágenes.
				Para obtener más información, consulte la documentación de Symbiota para ';
$LANG['A12'] = 'para la integración de imágenes.';	
$LANG['C'] = 'prácticas recomendadas';
$LANG['D'] = 'integrar imágenes.';
$LANG['E'] = 'Asignación de carga de archivos de imagen';
$LANG['F'] = 'Target (campo)';
$LANG['G'] = 'Source (campo)<';
$LANG['H'] = 'Perfiles de procesamiento de imágenes guardados';
$LANG['I'] = 'Perfil';
$LANG['J'] = 'Tipo de mapeo de imágenes:';
$LANG['K'] = 'Mapeo de imágenes local';
$LANG['L'] = 'Cargar archivo de asignación de imágenes';
$LANG['M'] = 'Informe de ingesta de medios iDigBio';
$LANG['N'] = 'iPlant cosecha de imágenes';
$LANG['O'] = 'Título:';
$LANG['P'] = 'Término de coincidencia de patrón:';
$LANG['Q'] = 'Expresión regular necesaria para extraer el identificador único del texto de origen. Por ejemplo, expresión regular/^ (WIS-L-d{7}) d */extraerá el número de catálogo WIS-L-0001234 del archivo de imagen llamado WIS-L-0001234_a. jpg. Para obtener más información sobre la creación de expresiones regulares,';
$LANG['R'] = 'Término del reemplazo:';
$LANG['S'] = 'Expresión regular opcional para la coincidencia en el número de catálogo que se reemplazará con el término de reemplazo.
			Ejemplo 1: expresión reemplazar término =';
$LANG['G'] = 'combinado con reemplazar cadena =';
$LANG['I_1'] = 'convertirá 0001234 = > Barcode-0001234. Ejemplo 2: expresión reemplazar término =';
$LANG['J_1'] = 'combined with empty replace string will convert XYZ-0001234 => 0001234.';
$LANG['T'] = 'Cadena de reemplazo:';
$LANG['U'] = 'Cadena de reemplazo opcional para solicitar coincidencias de términos de reemplazo de Expression en catalogNumber.';
$LANG['V'] = 'Ruta de origen de imagen:';
$LANG['W'] = 'Ruta del servidor iPlant a las imágenes de origen. La ruta de acceso debe ser accesible para la API de servicio de datos de iPlant. Los scripts se arrastrarán a través de todos los directorios secundarios dentro del destino. Las instancias de--INSTITUTION_CODE--y--COLLECTION_CODE--se reemplazarán dinámicamente con los códigos de institución y colección almacenados en la configuración de metadatos de colecciones. Por ejemplo,/home/shared/sernec/--INSTITUTION_CODE--/se destinaría a/Home/Shared/sernec/XYC/para la colección XYZ. Contacte con el gestor del portal para más detalles. Deje en blanco para utilizar la ruta predeterminada: ';
$LANG['X'] = 'Ruta del servidor o URL a la ubicación de la imagen de origen. Las rutas de servidor deben ser absolutas y grabables para el servidor Web (p. ej., Apache). Si se suministra una URL (p. ej., http://), el servidor Web debe configurarse para enumerar públicamente todos los archivos dentro del directorio, o la salida HTML puede simplemente enumerar todas las imágenes dentro de las etiquetas de anclaje. En todos los casos, los scripts intentarán arrastrarse a través de todos los directorios secundarios.';
$LANG['Y'] = 'Ruta de destino de la imagen:';
$LANG['Z'] = 'Ruta del servidor web donde se depositan los derivados de la imagen. El servidor Web (por ejemplo, el usuario Apache) debe tener acceso de lectura/escritura a este directorio. Si este campo se deja en blanco, se utilizará la dirección URL de imagen predeterminada del portal ($imageRootUrl).';
$LANG['A1'] = 'Ancho de píxel central:';
$LANG['A2'] = 'Ancho de la imagen web estándar. Si la imagen de origen es menor que este ancho, el archivo simplemente se copiará sin cambiar el tamaño.';
$LANG['A3'] = 'Ancho de píxel en miniatura:';
$LANG['A4'] = 'Ancho de la miniatura de la imagen. Width debe ser mayor que el tamaño de imagen dentro de las páginas de visualización de miniaturas.';
$LANG['A5'] = 'Ancho de píxel grande:';
$LANG['A6'] = 'Ancho de la versión grande de la imagen. Si la imagen de origen es menor que este ancho, el archivo simplemente se copiará sin cambiar el tamaño. Tenga en cuenta que el redimensionamiento de imágenes grandes puede estar limitado por los ajustes de configuración de PHP (p. ej. memory_limit). Si esto es un problema, tener este valor mayor que el ancho máximo de las imágenes de origen evitará errores relacionados con el remuestreo de imágenes grandes.';
$LANG['A7'] = 'Calidad JPG:';
$LANG['A8'] = 'La calidad JPG se refiere a la cantidad de compresión aplicada. Valor debe ser numérico y rango de 0 (peor calidad, archivo más pequeño) a 100 (mejor calidad, archivo más grande). Si null, 75 se utiliza como valor predeterminado.';
$LANG['A9'] = 'Miniatura:';
$LANG['A10'] = 'Crear una nueva miniatura a partir de la imagen de origen';
$LANG['B1'] = 'Importar miniatura desde la ubicación de origen (nombre de origen con sufijo _ TN. jpg)';
$LANG['B2'] = 'Asignar a la miniatura en la ubicación de origen (nombre de origen con sufijo _ TN. jpg)';
$LANG['B3'] = 'Imagen grande:';
$LANG['B4'] = 'Importar imagen de origen como versión grande';
$LANG['B5'] = ' Asignar a la imagen de origen como versión grande';
$LANG['B6'] = 'Importar versión grande desde la ubicación de origen (nombre de origen con sufijo _ LG. jpg)';
$LANG['B7'] = 'Asignar a la versión grande en la ubicación de origen (nombre de origen con sufijo _ LG. jpg)';
$LANG['B8'] = 'Excluir versión grande';
$LANG['B9'] = 'Seleccione el archivo de asignación de imágenes:';
$LANG['B10'] = 'Eliminar proyecto';
$LANG['C1'] = 'Seleccione el archivo de salida iDigBio Image Appliance';
$LANG['C2'] = 'Fecha de última ejecución:';
$LANG['C3'] = 'Fecha de inicio del procesamiento:';
$LANG['C4'] = 'Término del fósforo del patrón:';
$LANG['C5'] = 'Término del partido en:';
$LANG['C6'] = 'Número de catálogo';
$LANG['C7'] = 'Otros números de catálogo';
$LANG['C8'] = 'Término del reemplazo:';
$LANG['C9'] = 'Cadena de reemplazo:';
$LANG['D1'] = 'Ruta de origen:';
$LANG['D2'] = 'Carpeta de destino:';
$LANG['D3'] = 'Prefijo URL:';
$LANG['D4'] = 'Ancho de la imagen web:';
$LANG['D5'] = 'Ancho de la miniatura:';
$LANG['D6'] = 'Ancho de imagen grande:';
$LANG['D7'] = 'Calidad JPG (1-100):';
$LANG['D8'] = 'Imagen web:';
$LANG['D9'] = 'Evaluar e importar la imagen de origen';
$LANG['E1'] = 'Importar imagen de origen como está sin redimensionar';
$LANG['E2'] = 'a la imagen de origen sin importar';
$LANG['E3'] = 'Miniatura:';
$LANG['E4'] = 'Crear una nueva imagen de origen';
$LANG['E5'] = 'Importar miniatura de origen existente (nombre de origen con sufijo _ TN. jpg)';
$LANG['E6'] = 'Asignar a la miniatura de origen existente (nombre de origen con sufijo _ TN. jpg)';
$LANG['E7'] = 'Excluir miniatura';
$LANG['E8'] = 'Imagen grande:';
$LANG['E9'] = 'Importar imagen de origen como versión grande';
$LANG['F1'] = 'Asignar a la imagen de origen como versión grande';
$LANG['F2'] = 'Importar versión grande existente (nombre de origen con sufijo _ LG. jpg)';
$LANG['F3'] = 'Asignar a la versión grande existente (nombre de origen con sufijo _ LG. jpg)';
$LANG['F4'] = 'Excluir versión grande';
$LANG['F5'] = 'Falta el registro:';
$LANG['F6'] = 'Omitir importación de imágenes e ir al siguiente';
$LANG['F7'] = 'Crear una imagen de registro y enlace vacía';
$LANG['F8'] = 'La imagen ya existe:';
$LANG['F9'] = 'Omitir importación';
$LANG['F10'] = 'Cambie el nombre de la imagen y guárdelo';
$LANG['F11'] = 'Reemplazar imagen existente';
$LANG['F12'] = 'Buscar y procesar archivos esqueléticos (extensiones permitidas: CSV, txt, Tab, DAT):';
$LANG['F13'] = 'Omitir archivos esqueléticos';
$LANG['F14'] = 'Procesar archivos esqueléticos';
$LANG['F15'] = 'Archivos de registro';
?>
