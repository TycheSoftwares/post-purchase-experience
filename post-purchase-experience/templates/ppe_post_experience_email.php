<?php
/**
 * Printable ticket Template
 *
 * @author 		TycheSoftwares
 * @package 	post-purchase-experience/
 * @version     1.0
 */ 
if ( ! defined( 'ABSPATH' ) ) {
	//exit; // Exit if accessed directly
}
$first_name    = $order_obj->get_billing_first_name();
$last_name     = $order_obj->get_billing_last_name();
$product_items = $order_obj->get_items();
$order_id      = $order_obj->get_id();
$orddd_class   = new orddd_common();
$delivery_date = $orddd_class->orddd_get_order_delivery_date( $order_id );
$timeslot      = $orddd_class->orddd_get_order_timeslot( $order_id );
?>

<table width="650" border="0" cellspacing="0" cellpadding="0" align="center">
  	<tbody>
	  	<tr>
	    	<td valign="top" style="padding-bottom:10px">
				<h1 style="margin:0!important">
					<span style="font-size:21.0pt;font-family:&quot;Helvetica&quot;,&quot;sans-serif&quot;;color:#0a0a0e"><?php echo get_option( 'blogname' ); ?><u></u><u></u>
					</span>
				</h1>
	      		<p class="MsoNormal"><span style="font-size:11.5pt;font-family:&quot;Helvetica&quot;,&quot;sans-serif&quot;"><?php echo get_option( 'blogdescription' ); ?></span> 
	      		</p>
	    	</td>
	  	</tr>
	  	<tr>
	    	<td valign="top" style="padding-bottom:10px;font-family:verdana,arial,helvetica;font-size:16px"> Hi <?php echo $first_name . " " . $last_name; ?>, will you please take a minute to share your experience? </td>
	  	</tr>
	</tbody>
</table>

<center>
<table width="650" border="0" cellspacing="0" cellpadding="0" bgcolor="#cccc99">
	<tbody>
		<tr valign="top">
			<td style="border:2px #99ccff solid">
  				<table width="646" border="0" cellspacing="0" cellpadding="0" bgcolor="#cccc99" height="100%" align="center">
  					<tbody>
  						<tr bgcolor="#FFFFFF" valign="top">
           					<td>
            					<table width="98%" border="0" cellspacing="0" cellpadding="0" height="100%" align="center">
              						<tbody>
              							<tr valign="top">
                							<td colspan="2">
												<table border="0" cellpadding="2" cellspacing="0" width="100%">
												  	<tbody>
												  		<?php
												  		foreach( $product_items as $pkey => $pvalue ) {
												  			$product_id = $pvalue[ 'product_id' ];
												  			$product_name = get_the_title( $product_id );
												  			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'single-post-thumbnail' );
												  			$product_link = get_permalink( $product_id );
												  			?>
													  		<tr valign="top">
													    		<td width="100%">
													      			<table border="0" cellpadding="2" cellspacing="0" width="100%">
													        			<tbody>
													        				<tr>
													          					<td colspan="2" style="padding-top:6px;padding-bottom:10px;font-weight:bold;font-family:verdana,arial,helvetica;color:#cc6600">
													            					You Purchased:
													          					</td>
													        				</tr>    
																			<tr bgcolor="#ffffff">
																				<td valign="top" style="width:65px;padding-right:5px;padding-bottom:10px">
																					<img src="<?php  echo $image[0]; ?>" data-id="<?php echo $product_id; ?>">
																				</td>
      																			<td valign="top" style="font-weight:bold;margin-top:5px;margin-bottom:5px;font-family:verdana,arial,helvetica;font-size:14px">
  																					<?php echo $product_name; ?>
																				</td>
																			</tr>
																			<br>
																			<tr>
																		    	<td colspan="2">
																		        	<span style="font-size:12px;font-style:italic;font-weight:bold;">
																		          		Estimated delivery date: <?php echo $delivery_date; 
																		          		if( $timeslot != '' ) {
																		          			?>
																		          			<br>Estimated delivery time: <?php echo $timeslot;
																		          		}
																		          		?>
																		        	</span>
																		      </span>
																		    </td>
																	  	</tr>
																	</tbody>
																</table>

																<table border="0" cellpadding="2" cellspacing="0" width="100%">
																	<tbody>
																		<tr> 
   																			<td style="padding:0px 0 6px 0;font-size:14px;font-weight:bold;font-family:verdana,arial,helvetica"> 
  																				<br>Please select a rating for the seller based on these questions 
																			</td> 
  																		</tr>
																		<tr>
																			<td style="padding:2px 0 0 18px;font-size:15px;font-family:verdana,arial,helvetica">
																			  Item arrived by 21 April 2017?
																			</td>
																		</tr>
																		<tr>
																			<td style="padding:2px 0 0 18px;font-size:15px;font-family:verdana,arial,helvetica">
																			  Item as described by the seller?
																			</td>
																		</tr>
																		<tr>
																			<td style="padding:2px 0 0 18px;font-size:15px;font-family:verdana,arial,helvetica">
																				Prompt and courteous service?
																				<span style="margin-left:8px;color:gray;font-style:italic;font-size:11px"	>(If you 	contacted the seller)
																				</span>
																			</td>
																		</tr>
																	</tbody>
																</table>
																<table border="0" cellpadding="2" cellspacing="0" width="100%" style="margin-top:12px">
  																<tbody>
  																	<tr>
    																	<td width="20%" style="white-space:nowrap;font-size:14px;font-family:verdana,arial,helvetica;font-weight:normal;vertical-align:center;padding:2px 0 2px 18px">
      																		<a href="<?php echo $product_link . '?rating=5&#tab-reviews'?>">5 (Excellent)</a>
																		</td>
    																	<td style="font-size:14px;font-family:verdana,arial,helvetica;font-weight:bold">
      																		<img src="<?php echo $plugins_url . '/post-purchase-experience/images/5-star.gif'; ?>" alt="" class="star-rating">
																		</td>
	  																</tr>
	  																<tr>
    																	<td width="20%" style="white-space:nowrap;font-size:14px;font-family:verdana,arial,helvetica;font-weight:normal;vertical-align:center;padding:2px 0 2px 18px">
      																		<a href="<?php echo $product_link . '?rating=4&#tab-reviews'?>">4 (Good)</a>
																		</td>
    																	<td style="font-size:14px;font-family:verdana,arial,helvetica;font-weight:bold">
      																		<img src="<?php echo $plugins_url . '/post-purchase-experience/images/4-star.gif'; ?>" alt="" class="star-rating">
																		</td>
	  																</tr>
	  																<tr>
    																	<td width="20%" style="white-space:nowrap;font-size:14px;font-family:verdana,arial,helvetica;font-weight:normal;vertical-align:center;padding:2px 0 2px 18px">
      																		<a href="<?php echo $product_link . '?rating=3&#tab-reviews'?>">3 (Fair)</a>
																		</td>
    																	<td style="font-size:14px;font-family:verdana,arial,helvetica;font-weight:bold">
      																		<img src="<?php echo $plugins_url . '/post-purchase-experience/images/3-star.gif'; ?>" alt="" class="star-rating">
																		</td>
	  																</tr>
	  																<tr>
    																	<td width="20%" style="white-space:nowrap;font-size:14px;font-family:verdana,arial,helvetica;font-weight:normal;vertical-align:center;padding:2px 0 2px 18px">
      																		<a href="<?php echo $product_link . '?rating=2&#tab-reviews'?>">2 (Poor)</a>
																		</td>
    																	<td style="font-size:14px;font-family:verdana,arial,helvetica;font-weight:bold">
      																		<img src="<?php echo $plugins_url . '/post-purchase-experience/images/2-star.gif'; ?>" alt="" class="star-rating">
																		</td>
	  																</tr>
	  																<tr>
    																	<td width="20%" style="white-space:nowrap;font-size:14px;font-family:verdana,arial,helvetica;font-weight:normal;vertical-align:center;padding:2px 0 2px 18px">
      																		<a href="<?php echo $product_link . '?rating=1&#tab-reviews'?>">1 (Awful)</a>
																		</td>
    																	<td style="font-size:14px;font-family:verdana,arial,helvetica;font-weight:bold">
      																		<img src="<?php echo $plugins_url . '/post-purchase-experience/images/1-star.gif'; ?>" alt="" class="star-rating">
																		</td>
	  																</tr>
																</tbody>
															</table>
	    												</td>
	  												</tr>
	  												<hr>
	  												<?php } ?>
												</tbody>
											</table>
										</td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
				</tbody>
			</table>
		</td>
	</tr>
</tbody>
</table>