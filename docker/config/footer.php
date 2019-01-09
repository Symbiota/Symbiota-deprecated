    	</td>
	</tr>
	<tr>
		<td class="footer"  colspan="3">
            <?php
                $footer_config = yaml_parse_file($CONFIG_FILE_DIR . "/" . $CONFIG_FILE_FOOTER)["footer"];
                echo $footer_config["footer_html"];
            ?>
		</td>
	</tr>
</table>