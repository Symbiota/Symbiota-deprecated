#!/usr/bin/python3

import os
import sys
import yaml

from datetime import date
from string import Template

try:
    from yaml import CLoader as Loader, CDumper as Dumper
except ImportError:
    from yaml import Loader, Dumper

CONFIG_ROOT = "/usr/local/etc/symbiota"
CONFIG_FILE_INPUT = os.path.join(CONFIG_ROOT, "symbiota.yml")

TEMPLATE_DB = os.path.join(CONFIG_ROOT, "php", "dbconnection_template.php")
TEMPLATE_SYMBINI = os.path.join(CONFIG_ROOT, "php", "symbini_template.php")

TEMPLATE_HEADER = os.path.join(CONFIG_ROOT, "php", "header_template.php")
TEMPLATE_FOOTER = os.path.join(CONFIG_ROOT, "php", "footer_template.php")

CONFIG_FILE_DB = "{}/config/dbconnection.php"
CONFIG_FILE_SYMBINI = "{}/config/symbini.php"

CONFIG_FILE_HEADER = "{}/header.php"
CONFIG_FILE_FOOTER = "{}/footer.php"
CONFIG_FILE_INDEX = "{}/index.php"


def get_database_vars(config_data):
    db_config_data = config_data["database"]
    return {
        "db_host": db_config_data["host"],
        "db_port": db_config_data["port"],
        "db_name": db_config_data["name"],
        "db_user_readonly": db_config_data["users"]["readonly"]["username"],
        "db_user_readwrite": db_config_data["users"]["readwrite"]["username"],
        "db_password_readonly": db_config_data["users"]["readonly"]["password"],
        "db_password_readwrite": db_config_data["users"]["readwrite"]["password"]
    }

def get_symbini_vars(config_data):
    symbini_config_data = config_data["symbini"]
    return {
        "default_lang": symbini_config_data["core"]["default_lang"],
        "proj_id": symbini_config_data["core"]["proj_id"],
        "cat_id": symbini_config_data["core"]["cat_id"],
        "page_title": symbini_config_data["core"]["page_title"],
        "tid_focus": symbini_config_data["core"]["tid_focus"],
        "admin_email": symbini_config_data["core"]["admin_email"],
        "portal_guid": symbini_config_data["core"]["portal_guid"],
        "security_key": symbini_config_data["core"]["security_key"],
        "path_symbiota_root": symbini_config_data["data"]["path_symbiota_root"],
        "url_path_root": symbini_config_data["data"]["url_path_root"],
        "image_domain": symbini_config_data["data"]["image_domain"],
        "url_path_images": symbini_config_data["data"]["url_path_images"],
        "lbcc_activated": symbini_config_data["nlp"]["lbcc_activated"],
        "salix_activated": symbini_config_data["nlp"]["salix_activated"],
        "mod_occurrence": symbini_config_data["modules"]["occurrence"],
        "mod_flora": symbini_config_data["modules"]["flora"],
        "mod_key": symbini_config_data["modules"]["key"],
        "mod_tracking": symbini_config_data["modules"]["tracking"],
        "mod_fp": symbini_config_data["modules"]["filtered_push"],
        "geoserver_url": symbini_config_data["geoserver"]["url"],
        "geoserver_record_layer": symbini_config_data["geoserver"]["record_layer"],
        "solr_url": symbini_config_data["solr"]["url"],
        "solr_import_interval": symbini_config_data["solr"]["full_import_interval"],
        "gbif_username": symbini_config_data["gbif"]["username"],
        "gbif_password": symbini_config_data["gbif"]["password"],
        "gbif_ocr_key": symbini_config_data["gbif"]["ocr_key"],
        "google_anaylytics_key": symbini_config_data["misc"]["google"]["analytics_key"],
        "google_map_key": symbini_config_data["misc"]["google"]["map_key"],
        "google_map_zoom": symbini_config_data["misc"]["google"]["map_zoom"],
        "google_map_boundaries": symbini_config_data["misc"]["google"]["boundaries"],
        "spacial_center": symbini_config_data["misc"]["spacial"]["initial_center"],
        "spacial_zoom": symbini_config_data["misc"]["spacial"]["initial_zoom"],
        "taxon_center": symbini_config_data["misc"]["taxon_profile"]["map_center"],
        "taxon_zoom": symbini_config_data["misc"]["taxon_profile"]["map_zoom"],
        "recaptcha_public_key": symbini_config_data["misc"]["recaptcha"]["public_key"],
        "recaptcha_private_key": symbini_config_data["misc"]["recaptcha"]["private_key"],
        "georef_divisions": symbini_config_data["misc"]["georeference_political_divisions"],
        "eol_key": symbini_config_data["misc"]["eol_key"],
        "taxon_auth_cols": symbini_config_data["misc"]["taxonomic_authorities"]["COL"],
        "taxon_auth_worms": symbini_config_data["misc"]["taxonomic_authorities"]["WoRMS"],
        "quick_host_entry_active": symbini_config_data["misc"]["quick_host_entry_active"],
        "portal_taxa_desc": symbini_config_data["misc"]["portal_taxa_desc"],
        "glossary_export_banner": symbini_config_data["misc"]["glossary_export_banner"],
        "dyn_checklist_radius": symbini_config_data["misc"]["dyn_checklist_radius"],
        "display_common_names": symbini_config_data["misc"]["display_common_names"],
        "exsiccati": symbini_config_data["misc"]["exsiccati"],
        "checklist_fg_export": symbini_config_data["misc"]["checklist_fg_export"],
        "fieldguide_active": symbini_config_data["misc"]["fieldguide"]["active"],
        "fieldguide_api_key": symbini_config_data["misc"]["fieldguide"]["api_key"],
        "genbank_tool_path": symbini_config_data["misc"]["genbank_tool_path"],
        "geolocate_toolkit": symbini_config_data["misc"]["geolocate_toolkit"],
        "css_version_date": date.today().strftime("%Y%m%d"),
        "checklists_menu": symbini_config_data["navigation"]["checklists_menu"],
        "collections_index_menu": symbini_config_data["navigation"]["collections"]["index_menu"],
        "collections_harvest_params_menu": symbini_config_data["navigation"]["collections"]["harvest_params_menu"],
        "collections_list_menu": symbini_config_data["navigation"]["collections"]["list_menu"],
        "collections_checklist_menu": symbini_config_data["navigation"]["collections"]["checklist_menu"],
        "collections_download_menu": symbini_config_data["navigation"]["collections"]["download_menu"],
        "collections_maps_menu": symbini_config_data["navigation"]["collections"]["maps_menu"],
        "collections_loans_index_menu": symbini_config_data["navigation"]["collections"]["loans_index_menu"],
        "ident_key_menu": symbini_config_data["navigation"]["ident"]["key_menu"],
        "ident_chardeficit_menu": symbini_config_data["navigation"]["ident"]["chardeficit_menu"],
        "ident_mass_update_menu": symbini_config_data["navigation"]["ident"]["mass_update_menu"],
        "ident_editor_menu": symbini_config_data["navigation"]["ident"]["editor_menu"],
        "taxa_index_menu": symbini_config_data["navigation"]["taxa"]["index_menu"],
        "taxa_admin_tpeditor_menu": symbini_config_data["navigation"]["taxa"]["admin_tpeditor_menu"],
        "glossary_index_banner": symbini_config_data["navigation"]["glossary_index_banner"],
        "agents_index_menu": symbini_config_data["navigation"]["agents"]["index_menu"],
        "agent_index_crumbs": symbini_config_data["navigation"]["agents"]["index_crumbs"]
    }

def get_header_vars(config_data):
    return {
        "header_image": config_data["header"]["header_image"]
    }

def get_footer_vars(config_data):
    return {
        "footer_html": config_data["footer"]["footer_html"]
    }

# Read config
with open(CONFIG_FILE_INPUT) as f:
    config_data = yaml.load(f.read(), Loader=Loader)

for k, v in config_data.items():
    if config_data[k] is None:
        config_data[k] = ''

db_config = get_database_vars(config_data)
symbini_config = get_symbini_vars(config_data)
header_config = get_header_vars(config_data)
footer_config = get_footer_vars(config_data)

# Generate config files
with open(TEMPLATE_DB) as f:
    db_output = Template(f.read()).substitute(db_config)

with open(TEMPLATE_SYMBINI) as f:
    symbini_output = Template(f.read()).substitute(symbini_config)

with open(TEMPLATE_HEADER) as f:
    header_output = Template(f.read()).substitute(header_config)

with open(TEMPLATE_FOOTER) as f:
    footer_output = Template(f.read()).substitute(footer_config)

# Write dbconnection.php
with open(CONFIG_FILE_DB.format(symbini_config["path_symbiota_root"]), 'w') as f:
    f.write(db_output)

# Write symbini.php
with open(CONFIG_FILE_SYMBINI.format(symbini_config["path_symbiota_root"]), 'w') as f:
    f.write(symbini_output)

# Write header.php
with open(CONFIG_FILE_HEADER.format(symbini_config["path_symbiota_root"]), 'w') as f:
    f.write(header_output)

# Write footer.php
with open(CONFIG_FILE_FOOTER.format(symbini_config["path_symbiota_root"]), 'w') as f:
    f.write(footer_output)