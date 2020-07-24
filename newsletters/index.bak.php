<?php
//error_reporting(E_ALL);
include_once( "../config/symbini.php" );
header( "Content-Type: text/html; charset=" . $charset );
?>
<html>
<head>
    <title><?php echo $defaultTitle ?> Home</title>
    <meta charset="UTF-8">
    <link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet"/>
    <link href="../css/main.css?<?php echo $CSS_VERSION_LOCAL; ?>" type="text/css" rel="stylesheet"/>
    <meta name='keywords' content=''/>
    <script type="text/javascript">
		<?php include_once( $serverRoot . '/config/googleanalytics.php' ); ?>
    </script>
    <style type="text/css">

    </style>
</head>
<body>
<?php
include( $serverRoot . "/header.php" );
?>

<!-- if you need a full width colum, just put it outside of .inner-content -->
<!-- .inner-content makes a column max width 1100px, centered in the viewport -->
<div class="inner-content">
    <!-- place static page content here. -->
<div class="newsletter-content">
	<?php

	//$table_output could contain either a search result, all of the newsletters or the admin page

	function str_limit( $str ) {
		if ( strlen( $str ) > 100 ) {
			$new_str = substr( $str, 0, 100 );
			$new_str .= "...";

			return $new_str;
		} else {
			return $str;
		}
	}

	//connect to the database
	$con = MySQLiConnectionFactory::getCon( "readonly" );

	if ( ! $con ) {
		die( "There was a problem with the database." );
	}

	$new_issues = mysqli_query( $con, "select volume, issue, pdf from articles group by volume, issue, pdf order by volume desc, issue desc limit 2" );

	if ( ! $new_issues ) {
		echo "no new issues";
	}

	$row = mysqli_fetch_assoc( $new_issues );
	echo "<b>Current Issue:</b><a href='http://oregonflora.org/ofn/" . $row['pdf'] . ".pdf' target='_blank'>Vol." . $row['volume'] . " Iss." . $row['issue'] . "</a><br />";
	$row = mysqli_fetch_assoc( $new_issues );
	echo "<b>Previous Issue:</b><a href='http://oregonflora.org/ofn/" . $row['pdf'] . ".pdf' target='_blank'>Vol." . $row['volume'] . " Iss." . $row['issue'] . "</a><br />";
	?>
    <h2>Search Newsletters</h2>
    <form action='index.php' method='POST'>
        search <input type='text' name='search_keyword'/>search: <input type='radio' name='search_type'
                                                                        value='keywords'/> keywords <input
                type='radio' name='search_type' value='title'/>title <input type='radio' name='search_type'
                                                                            value='authors'/>authors
        <button type='submit' class="btn green-btn">enter</button>
    </form>
	<?php

	if ( ! isset( $_POST['search_keyword'] ) ) {
		//not searching by keywords
		?>
        <div class='nl_block' style='background-color:#d1dac7;'>
	<span class='nl_titleBlock'>
    	<span class='nl_title'>
        	<a id='toggle_all' href='#'>Expand All Newsletters</a>
        </span>
    </span>
            <div><strong>Tip: </strong></div>
            <div class="nl_tips">
                <span style='font-size:10px;'>Clicking the 'Vol:' link expands hidden newsletter articles.</span>

                <span class='a_title'>
        	<div class='a_editTitle'><a>Article Title (links to a pdf):</a></div>
        </span>
        </div>
            <div class="nl_block_author">
                <span class='a_authors'>
        	<span class='a_editAuthors'><a>Authors:</a></span>
        </span>
            </div>
            <br/>
        </div>

		<?php

		$results = mysqli_query( $con, "select volume, issue, issue_str, title, authors, pdf, article_order  
    from articles group by volume, issue, article_order, issue_str, title, authors, pdf 
    order by volume desc, issue desc, article_order asc;" );

		if ( ! $results ) {
			echo "problem with the query";
		} else if ( mysqli_num_rows( $results ) == 0 ) {
			echo "no newsletters have been entered into the database";
		} else {
			$outer_row_count = 0;
			$first           = true;
			while ( $row = mysqli_fetch_assoc( $results ) ) {
				if ( $row['article_order'] == 1 ) {
					$outer_row_count ++;

					if ( ! $first ) {
						//close the inner and outer table
						echo "</div><!-- .inner -->\n</div><!-- .outer -->\n";
					}
					$first = false;
					?>

                    <div class="outer">
                    <div class="news-item-wrapper">
                        <div class="news-vol">
                            <a class='collapsible'href='#'>
                            Vol. <?php echo $row['volume'] ?>
                            Iss. <?php echo $row['issue_str'] ?>
                            </a>
                            <br/>Vol. <?php echo $row['volume'] ?> Iss. <?php echo $row['issue'] ?>
                        </div>
                        <div class="news-title">
                            <a href='http://oregonflora.org/ofn/<?php echo $row["pdf"] ?>.pdf'
                               target='_blank'><?php echo str_limit( $row["title"] ) ?></a>
                        </div>
                        <div class="news-authors">
							<?php echo $row["authors"] == null ? "n/a" : $row["authors"] ?>
                        </div>
                    </div>
                    <div class="inner">


					<?php
				} else {
					//append to the inner table
					?>
                    <div class="inner-wrapper">
                        <div class="inner-title">
                            <?php echo str_limit( $row["title"] ) ?>
                        </div>
                        <div class="inner-author">
                            <?php echo $row["authors"] == null ? "n/a" : $row["authors"] ?>
                        </div>
                    </div>

					<?php

				}
			}


		}
	} /* ABOUT SEARCHING
	 *
	 * To allow for case-insensitive searching the keyword sent is always
	 * converted to lowercase and the comparisons are done lower case
	 * but the results returned are always in their original cases
	 */
	else {
		//$table_output is now the search results

		$keyword = strtolower( mysqli_escape_string( $con, $_POST['search_keyword'] ) );
		$results = "";

		if ( isset( $_POST["search_type"] ) ) {
			if ( $_POST["search_type"] == "authors" ) {
				$results = mysqli_query( $con, "select volume, issue, issue_str, volume_year, title, authors, pdf 
            from articles where lower(authors) like '%" . $keyword . "%' group by volume, issue, issue_str, title, volume_year, 
            authors, pdf order by volume desc, issue desc;" );
			} else if ( $_POST["search_type"] == "keywords" ) {
				$results = mysqli_query( $con, "select volume, issue, issue_str, volume_year, title, authors, pdf 
            from articles, keywords where articles.articles_id = keywords.articles_id and lower(keyword) like '%" . $keyword . "%' 
            group by volume, issue, issue_str, volume_year, authors, title, pdf order by volume desc, issue desc;" );
			} else if ( $_POST["search_type"] == "title" ) {
				$results = mysqli_query( $con, "select volume, issue, issue_str, volume_year, title, authors, pdf from articles where lower(title) like '%" . $keyword . "%' group by 
            volume, issue, issue_str, title, volume_year, authors, pdf order by volume desc, issue desc;" );
			} else {
				//search by keyword by default

				$results = mysqli_query( $con, "select volume, issue, issue_str, volume_year, title, authors, pdf 
            from articles, keywords where articles.articles_id = keywords.articles_id and lower(keyword) like '%" . $keyword . "%' 
            group by volume, issue, issue_str, volume_year, authors, title, pdf order by volume desc, issue desc;" );
			}
		} else {
			//search by keyword by default

			$results = mysqli_query( $con, "select volume, issue, issue_str, volume_year, title, authors, pdf 
        from articles, keywords where articles.articles_id = keywords.articles_id and lower(keyword) like '%" . $keyword . "%' 
        group by volume, issue, issue_str, volume_year, authors, title, pdf order by volume desc, issue desc;" );

		}
		?>

        <div class='nl_block' style='background-color:#d1dac7;'>
	<span class='nl_titleBlock'>
    	<span class='nl_title'>
        	Search Results for <?php echo $keyword ?>
        </span>
    </span>
        </div>

		<?php

		if ( ! $results ) {
			echo "<h2>database error</h2>";
		} else if ( mysqli_num_rows( $results ) == 0 ) {
			echo"<h3>no articles found.</h3>";
		} else {
			while ( $row = mysqli_fetch_assoc( $results ) ) {
				?>
                <div class="inner-wrapper">
                    <div class="inner-volume">
                        Vol. <?php echo $row['volume'] ?> Iss. <?php echo $row['issue_str'] ?>
                    </div>
                    <div class="inner-title">
                        <a href='http://oregonflora.org/ofn/<?php echo $row['pdf'] ?>.pdf'><?php echo $row['title'] ?></a>
                    </div>
                    <div class="inner-author">
	                    <?php echo $row['authors'] == null ? "n/a" : $row['authors'] ?>
                    </div>
                </div>

				<?php
			}
		}
	}


	?>
</div>
</div>
<?php
include( $serverRoot . "/footer.php" );
?>

<script type="text/javascript">
    $(function () {
        //hide all newsletters on load
        $(".inner").each(function (index, element) {
            $(this).hide(0);
        });

        //set each newsletter to be collapsible
        $(".outer").each(function (index, element) {
            $(element).find("a:first").click(function (event) {
                event.preventDefault();

                //find the inner table
                var inner_table = $(element).find(".inner:first");
                if (inner_table.is(":visible") == true) {
                    inner_table.hide("slow");
                }
                else {
                    inner_table.show("slow");
                }
            });
        });
        /*$(".collapsible").click(function(event) {
			event.preventDefault();
			var inner = $(this).closest("table.outer").find("table.inner");

			if(inner.is(":visible") == true) {
				inner.hide("slow");
			}
			else {
				inner.show("slow");
			}
		});*/

        //set expand all newsletters to expand them
        $("#toggle_all").click(function (event) {
            event.preventDefault();

            if ($("#toggle_all").text() == "Expand All Newsletters") {
                //loop through each inner table and show it
                $(".inner").each(function (index, element) {
                    if ($(element).is(":hidden") == true) {
                        $(element).show(0);
                    }
                });

                //change the text of the button
                $("#toggle_all").text("Collapse All Newsletters");
            }
            else {
                $(".inner").each(function (index, element) {
                    if ($(element).is(":visible") == true) {
                        $(element).hide(0);
                    }
                });

                $("#toggle_all").text("Expand All Newsletters");
            }
        });
    });
</script>
</body>
</html>