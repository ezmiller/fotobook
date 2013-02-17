<div class="fotobook-subheader">
  <span class='main'>Albums <?php echo $first_album ?> - <?php echo $last_album ?> out of <?php echo $album_count ?></span>
  <div class='pagination'>
    <?php if($prev_link): ?><a href='<?php echo $prev_link ?>'>Prev</a><?php endif; ?>
    <?php echo $pagination ?>
    <?php if($next_link): ?><a href='<?php echo $next_link ?>'>Next</a><?php endif; ?>
  </div>
</div>

<div id="fotobook-album-list">
<?php
if(sizeof($albums) > 0):
foreach($albums as $album):
?>
   <div class="fotobook-album-entry clearfix">
    <div class="fotobook-album-thumb clearfix">
      <a href="<?php echo $album['link'] ?>">
          <span class="fotobook-album-thumb-wrap">
            <img src="<?php echo $album['thumb'] ?>" alt="<?php echo $album['name'] ?>" />
          </span>
      </a>
    </div>
    <div class="fotobook-album-meta clearfix">
      <a href='<?php echo $album['link'] ?>'><?php echo $album['name']; ?></a><br/>
      <?php if($album['description'] != ''): echo '<span class="description">' . $album['description'] . '</span><br/>' ?><?php endif; ?>
      <span class="album-size"><?php echo $album['size'] ?> photos</span>
    </div>
  </div>
<?php 
endforeach; 
endif; ?>
</div><!-- end #fotobook-album-list -->

<div class="fotobook-subheader fotobook-subheader-bottom">
  <span class='main'>Albums <?php echo $first_album ?> - <?php echo $last_album ?> out of <?php echo $album_count ?></span>
  <div class='pagination'>
    <?php if($prev_link): ?><a href='<?php echo $prev_link ?>'>Prev</a><?php endif; ?>
    <?php echo $pagination ?>
    <?php if($next_link): ?><a href='<?php echo $next_link ?>'>Next</a><?php endif; ?>
  </div>
</div>