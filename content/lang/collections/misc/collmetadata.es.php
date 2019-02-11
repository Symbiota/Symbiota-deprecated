<?php 
/*
------------------
Language: Español (Spanish)
------------------
*/

include_once('sharedterms.es.php');


$LANG['CREATE'] = 'Crear nueva colección u observación';
$LANG['COD'] = 'Código de institución:';
$LANG['THE'] = 'El nombre (o acrónimo) en uso por la institución que tiene custodia de los registros de ocurrencia. Este campo es obligatorio. Para más detalles, vea';
$LANG['DARWIN'] = 'Darwin Core definition';
$LANG['COD_1'] = 'Código de colección:';
$LANG['MORE'] = 'Más información sobre el Código de Colección.';
$LANG['THE_NAME'] = 'El nombre, el acrónimo o el código que identifica la recopilación o el conjunto de datos del que se derivó el registro. Este campo es opcional. Para más detalles, ver';
$LANG['NAME_COL'] = 'Nombre de colección:';
$LANG['DES'] = 'Descripción<';
$LANG['MAX'] = '(máximo 2000 carácteres):';
$LANG['PAG'] = 'Página web:';
$LANG['CONTA'] = 'Contacto:';
$LANG['MAIL'] = 'E-mail:';
$LANG['LATITUDE'] = 'Latitud:';
$LANG['LONG'] = 'Longitud:';
$LANG['CATE'] = 'Categoria:';
$LANG['NO_CATE'] = 'Ninguna Categoria';
$LANG['PERM_EDI'] = 'Permitir ediciones públicas:';
$LANG['LA'] = ' La verificación de ediciones públicas permitirá a cualquier usuario que haya iniciado sesión en el sistema modificar los registros de ocurrencia
y resolver los errores encontrados dentro de la colección. Sin embargo, si el usuario no tiene explícita
autorización para la colección dada, las ediciones no se aplicarán hasta que sean
revisado y aprobado por el administrador de la colección.';
$LANG['LICEN'] = 'Licencia:';
$LANG['AL_LEGAL'] = 'Un documento legal que da permiso oficial para hacer algo con el recurso.
Este campo se puede limitar a un conjunto de valores modificando el portal en el archivo de configuración central.
Para más detalles, ver';
$LANG['DAR_DEFI'] = 'Definición de Darwin Core';
$LANG['TIT'] = 'Titular de los derechos:';
$LANG['THE_ORG'] = 'La organización o persona que administra o posee los derechos del recurso.
Para más detalles, ver ';
$LANG['DER'] = 'Derechos de acceso:';
$LANG['INFOR'] = 'Informaciones o un enlace de URL a la página con detalles que explican cómo se pueden usar los datos.Ver';
$LANG['FUENT'] = 'Fuente GUID:';
$LANG['NO_DEF'] = 'No definida';
$LANG['OCURRENCE'] = 'La Id. De ocurrencia se usa generalmente para los conjuntos de datos de instantáneas cuando un campo de Identificador único global (GUID)
es suministrado por la base de datos de origen (por ejemplo, Especifique la base de datos) y el GUID se asigna al ';
$LANG['OCURRENCE_ID'] = 'occurrenceId';
$LANG['FILED'] = 'campo.El uso de la Id. de ocurrencia como GUID no se recomienda para conjuntos de datos en vivo.
El Número de catálogo se puede usar cuando el valor dentro del campo de número de catálogo es globalmente único.
La opción GUID (UUID) generada por Symbiota activará el portal de datos Symbiota para que
generar UIDU GUIDs para cada registro. Esta opción se recomienda para muchos para Live Datasets
pero no permitido para las colecciones de instantáneas que se administran en el sistema de administración local.';
$LANG['PUBLISH'] = ' Publicar en agregadores:';
$LANG['URL'] = 'URL de registro de origen:';
$LANG['ADDING'] = 'Agregar una plantilla de URL aquí generará dinámicamente y agregará un enlace al registro de origen en la página de detalles de ocurrencia. Por ejemplo, & quot; http: //sweetgum.nybg.org/vh/specimen.php? Irn = - DBPK - & quot;
generará una url a la colección NYBG con & quot; - DBPK - & quot; siendo reemplazado con el
NYBG es la clave principal (campo de datos dbpk dentro de la tabla de ommoccurrence). El patrón de plantilla --CATALOGNUMBER-- también se puede utilizar en lugar de --DBPK--';
$LANG['AGRE'] = 'Agregar ícono:';
$LANG['ENTER'] = 'Introducir URL';
$LANG['UPLOAD'] = 'Subir imagen local';
$LANG['UP_ICON'] = 'Suba un archivo de imagen de icono o ingrese la URL de un icono de imagen que representa la colección. Si ingresa la URL de una imagen que ya se encuentra en un servidor, haga clic en; Ingresar URL;. La ruta URL puede ser absoluta o relativa. El uso de iconos es opcional.';
$LANG['TIPO'] = 'Tipo de colección:';
$LANG['SPEIMEN'] = 'Especímenes preservados';
$LANG['OBSER'] = 'Observaciones en general';
$LANG['PRESER'] = 'Preservar muestras significa que existen muestras físicas y que pueden ser inspeccionadas por los investigadores.
Utilice las observaciones cuando el registro no se base en una muestra física. Las observaciones generales se utilizan para configurar proyectos grupales donde los usuarios registrados pueden administrar de forma independiente su propio conjunto de datos directamente dentro de la única colección. Los investigadores de campo suelen utilizar las colecciones de observación general para gestionar sus datos de colección e imprimir etiquetas antes de depositar el material físico dentro de una colección. Aunque las colecciones personales están representadas por una muestra física, se clasifican como; observaciones; hasta que el material físico se deposite dentro de una colección disponible públicamente con curación activa.';
$LANG['ADMIN'] = 'Administración';
$LANG['INST'] = 'Instantánea';
$LANG['ADD'] = 'Agregar';
$LANG['USE'] = 'Use la instantánea cuando haya una base de datos interna separada que se mantiene en la colección y el conjunto de datos dentro del portal de Symbiota es solo una instantánea actualizada periódicamente de la base de datos central.
Un conjunto de datos en vivo es cuando los datos se administran directamente dentro del portal y la base de datos central son los datos del portal.';
$LANG['ORDER'] = 'Ordenar secuencia:';
$LANG['LEAVE'] = 'Deje este campo vacío si desea que las colecciones se ordenen alfabéticamente (predeterminado)';
$LANG['ID_GLOBAL'] = 'ID global única:';
$LANG['GLOBAL_UNIQUE'] = 'Identificador único global para esta colección. Si su colección ya tiene un GUID (por ejemplo, previamente asignado por una aplicación de administración de colecciones como Especificar), ese identificador debe estar representado aquí. Si necesita cambiar este valor, póngase en contacto con su administrador del portal.';
$LANG['SECURITY'] = 'Clave de seguridad:';
$LANG['ID_GLO'] = 'ID global única:';
$LANG['IDENTY'] = 'Identificador único global para esta colección.
Si su colección ya tiene un GUID (por ejemplo, previamente asignado por un
aplicación de gestión de colecciones, como Especificar), ese identificador se debe introducir aquí.
Si lo deja en blanco, el portal automáticamente
generar un UUID para esta colección (recomendado si no se sabe que GUID ya existe).';
$LANG['INSTI'] = 'Institución asociada';
$LANG['NO_EXIS'] = 'No existe institución asociada';
$LANG['SEL'] = 'Seleccionar Institución';
$LANG['ADD_IN'] = 'Añadir institución';
$LANG['ESP_VIVA'] = 'Especie viva';
$LANG['ID_OC'] = 'ID de ocurrencia';
$LANG['CAT_NUMBER'] = 'Número de catalogo';
$LANG['SYM'] = 'Generado por Symbiota  GUID (UUID)';
$LANG['OB'] = 'Observaciones';

$LANG['COLLECTION_PROFILES'] = 'Perfiles de colección';
$LANG['ALERT_INSTITUTION_CODE_MUST'] = '"Código de la institución debe tener un valor"';
$LANG['ALERT_COLLECTION_NAME_MUST_HAVE'] = '"El nombre de la colección debe tener un valor"';
$LANG['ALERT_THE_SYMBIOTA_GENERATED_GUID'] = '"La opción GUID generada por Symbiota no se puede seleccionar para una colección que se administra localmente fuera del portal de datos (por ejemplo, tipo de administración de instantáneas). En este caso, el GUID se debe generar dentro de la base de datos de la colección de origen y entregarse al portal de datos como parte del proceso de carga."';
$LANG['ALERT_LATITUDE_AND_LONGITUDE'] = '"Los valores de latitud y longitud deben estar en formato decimal (solo numérico)"';
$LANG['ALERT_RIGHTS_FIELD'] = '"El campo de derechos (por ejemplo, la licencia de Creative Commons) debe tener una selección"';
$LANG['ALERT_SORT_SEQUENCE_MUST'] = '"La secuencia de clasificación debe ser numérica solamente"';
$LANG['ALERT_THE_SYMBIOTA_GENERATED'] = '"La opción GUID generada por Symbiota no se puede seleccionar para una colección que se administra localmente fuera del portal de datos (por ejemplo, tipo de administración de instantáneas). En este caso, el GUID se debe generar dentro de la base de datos de la colección de origen y entregarse al portal de datos como parte del proceso de carga."';
$LANG['ALERT_AND_AGGREGATE_DATASET'] = '"Un conjunto de datos agregado (por ejemplo, las apariciones que provienen de varias colecciones) solo puede tener el ID de ocurrencia seleccionado para la fuente GUID"';
$LANG['ALERT_YOU_MUST_SELECT_A_GUID'] = '"Debe seleccionar una fuente GUID para publicar en los agregadores de datos."';
$LANG['ALERT_SELECT_AN_INSTITUTION_TO_BE'] = '"Seleccione una institución a vincular"';
$LANG['ALERT_THE_FILE_YOU_HAVE_UPLOADED'] = '"El archivo que ha cargado no es un archivo de imagen compatible. Por favor, cargue un archivo jpg, png o gif."';
$LANG['ALERT_THE_IMAGE_FILE_MUST'] = '"El archivo de imagen debe tener menos de 350 píxeles de ancho y alto."';
$LANG['ALERT_THE_URL_YOU_HAVE_ENTERED'] = '"La URL que ha ingresado no es para un archivo de imagen compatible. Por favor ingrese una url para un archivo jpg, png o gif."';
$LANG['HOME'] = 'Inicio';
$LANG['COLLECTION_MANAGER'] = 'Gestión de cobro';
$LANG['METADATA_EDITOR'] = 'Editor de metadata';
$LANG['CREATE_NEW_COLLECTION_PROFILE'] = 'Crear nuevo perfil de colección';
$LANG['MORE_INFORMATION_ABOUT_INSTITUTION_CODE'] = 'Más información sobre el Código de Institución.';
$LANG['REQUIRED_FIELD'] = 'Campo requerido';
$LANG['MORE_INFORMATION_ABOUT_PUBLIC_EDITS'] = 'Más información sobre Ediciones Públicas.';
$LANG['ORPHANED_TERM'] = 'término huérfano';
$LANG['MORE_INFORMATION_ABOUT_RIGHTS'] = 'Más información sobre los derechos.';
$LANG['MORE_INFORMATION_ABOUT_RIGHTS_HOLDER'] = 'Más información sobre el titular de los derechos.';
$LANG['MORE_INFORMATION_ABOUT_ACCESS_RIGHTS'] = 'Más información sobre derechos de acceso.';
$LANG['SOURCE_OF_GLOBAL_UNIQUE_IDENTIFIER'] = 'Fuente del identificador único global';
$LANG['MORE_INFORMATION_ABOUT_GLOBAL_UNIQUE_IDENTIFIER'] = 'Más información sobre el identificador único global';
$LANG['MORE_INFORMATION_ABOUT_PUBLISHING_TO_AGGREGATORS'] = 'Más información sobre la publicación a agregadores';
$LANG['OCURRENCE_ID_O'] = 'ID de ocurrencia';
$LANG['DYNAMIC_LINK_TO_SOURCE_DATABASE_INDIVIDUAL'] = 'Enlace dinámico a la página de registro individual de la base de datos de origen';
$LANG['MORE_INFORMATION_ABOUT_SOURCE_RECORDS_URL'] = 'Más información sobre la URL de los registros de origen';
$LANG['ADDING_A_URL_TEMPLATE_HERE_WILL'] = 'Agregar una plantilla de URL aquí generará dinámicamente y agregará a la página de detalles de ocurrencia un enlace a la registro fuente Por ejemplo, &quot;http://sweetgum.nybg.org/vh/specimen.php?Irn=--DBPK--&quot; generará una url a la colección NYBG con &quot;--DBPK--&quot; siendo reemplazado con el NYBGs Primary Key (campo de datos dbpk dentro de la tabla de ommoccurrence). El patrón de plantilla --CATALOGNUMBER-- también se puede utilizar en lugar de --DBPK--';
$LANG['ENTER_URL'] = 'Introducir URL';
$LANG['WHAT_IS_A_ICON'] = '¿Qué es un icono?';
$LANG['PRESERVED_SPECIMENS'] = 'Especímenes conservados';
$LANG['OBSERVATIONS'] = 'Observaciones';
$LANG['GENERAL_OBSERVATIONS'] = 'Observaciones generales';
$LANG['MORE_INFORMATION_ABOUT_COLLECTION_TYPE'] = 'Más información sobre el tipo de colección';
$LANG['MORE_INFORMATION_ABOUT_MANAGEMENT_TYPE'] = 'Más información sobre el tipo de gestión';
$LANG['MORE_INFORMATION_ABOUT_SORTING'] = 'Más información sobre la clasificación';
$LANG['MORE_INFORMATION'] = 'Más información';
$LANG['CREATE_NEW_COLLECTION'] = 'Crear nueva colección';
$LANG['EDIT_INSTITUTION_ADDRESS'] = 'Editar la dirección de la institución';
$LANG['UNLINK_INSTITUTION_ADDRESS'] = 'Desvincular dirección de la institución';
$LANG['SELECT_INSTITUTION_ADDRESS'] = 'Seleccione la dirección de la institución';
$LANG['LINK_ADDRESSS'] = 'Dirección de enlace';
$LANG['ADD_A_NEW_ADDRESS_NOT_ON_THE_LIST'] = 'Agregar una nueva dirección que no esté en la lista';
$LANG['LIVE_DATA'] = 'Datos en tiempo real';
$LANG['AGGREGATE'] = 'Agregar';
$LANG['SAVE_EDITS'] = 'Guardar cambios';

?>