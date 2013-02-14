<?php // DISPLAY PAGINATION AND LINK BACK TO MAIN PAGE ?>
<div class="fotobook-subheader album"> 
  <div class='pagination'>
    <?php if($prev_link): ?><a class="previous" href='<?php echo $prev_link ?>'><span class="meta-nav">&laquo;</span> Previous Photos</a><?php endif; ?>
     <?php if($next_link): ?><a class="next" href='<?php echo $next_link ?>'>More Photos <span class="meta-nav">&raquo;</span></a><?php endif; ?>
  </div>
  <span class='main'><!--Photos <?php //echo ($first_photo)." - ".($last_photo) ?> out of <?php //echo $photo_count ?> |--> <a href='<?php echo $albums_page_link ?>'>Back to Albums</a></span>
</div>

<?php // BUILD THE PHOTO TABLE ?>

<div id="fotobook-album">
    <div class="row">
    <?php foreach($photos as $key=>$photo): ?>
      <div class="photo">
        <?php $link = $page_link.(strstr($page_link, '?') ? '&amp;photo='.$key : '?photo='.$key) ?>
        <a href='<?php echo $link ?>' title="<?php echo $photo['caption'] ?>">
          <img src='<?php echo $photo['src'] ?>' alt="<?php echo $photo['caption'] ?>" style='max-width: <?php echo $thumb_size ?>px; max-height: <?php echo $thumb_size ?>px; _width: expression(this.width > <?php echo $thumb_size ?> ? <?php echo $thumb_size ?>: true); _height: expression(this.height > <?php echo $thumb_size ?> ? <?php echo $thumb_size ?>: true);' />
        </a>
      </div>
      <?php
      if($key % $number_cols == 0) { echo '</div><div class="row">'; }
      endforeach;
      /*for($i = 0; $i < ($number_cols - (count($photos) % $number_cols)); $i++) {
        echo "<td>&nbsp;</td>";
      }*/
      ?>
  </div>      
</div>

<div class="fotobook-subheader fotobook-subheader-bottom album">
  <div class='pagination'>
     <?php if($prev_link): ?><a class="previous" href='<?php echo $prev_link ?>'><span class="meta-nav">&laquo;</span> Previous Photos</a><?php endif; ?>
     <?php if($next_link): ?><a class="next" href='<?php echo $next_link ?>'>More Photos <span class="meta-nav">&raquo;</span></a><?php endif; ?>
  </div>
</div>

<?php // DISPLAY THE ALBUM INFO  ?>
<table id="fotobook-info">
<?php if($description): ?>
  <tr>
    <th>Description:</th>
    <td><?php echo $description ?></td>
  </tr>
<?php endif; ?>
<?php if($location): ?>
  <tr>
    <th>Location:</th>
    <td><?php echo $location ?></td>
  </tr>
<?php endif; ?>
</table>