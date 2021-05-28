<?php 

    function get_genre($item){
        $image = get_field('image', 'term_' . $item->term_id)['url'];

        $line = array('id' => $item->term_id, 'line_name'=> $item->name, 'line_slug' => $item->slug,  'source' => $item->taxonomy, 'image_default' => $image);

        global $lines;
        array_push($lines, $line);
        return;
    }

    function get_line_post($args){

        $items = array();

        $posts = get_posts($args);
        foreach ($posts as $post){
            $id =                   $post->ID;
            $title =                $post->post_title;
            $slug =                 $post->post_name;
            $video_type =           get_field('post_video_type', $id);
            $video_episode =        get_field('video_episode', $id);
            $subtitle =             get_field('post_subtitle', $id);
            $description =          wp_strip_all_tags(get_field('post_blurb', $id));
            $video_host =           get_field('post_video_host', $id);
            $video_id =             get_field('post_video_id', $id);
            $collection =           get_the_terms($id, 'collection')[0];
            $post_download_link =   get_field('post_download_link', $id);
            $genre =                get_the_terms($id, 'genre')[0];
            
            $video_thumbnail =      !is_null(get_field('video_thumbnail', $id)['url']) ? get_field('video_thumbnail', $id)['url'] : get_field('video_image_hover', $id)['url'];
            $video_image_hover =    !is_null(get_field('video_image_hover', $id)['url']) ? get_field('video_image_hover', $id)['url'] : false;
            
            $values = array('id' => $id,'title' => $title, 'slug' => $slug, 'video_type' => $video_type, 'video_episode' => $video_episode, 'subtitle' => $subtitle, 'description' => $description, 'genre' => $genre, 'collection' => $collection, 'video_host' => $video_host, 'video_id' => $video_id, 'post_download_link' => $post_download_link, 'video_thumbnail' => $video_thumbnail, 'video_image_hover' => $video_image_hover);
            array_push($items, $values);
        }

        return $items;
    }


    function get_line_collection($args){
        $items = array('included' => [], 'exclude' => []);

        $collections = get_terms($args);

        foreach($collections as $collection){
            $id = $collection->term_id;
            $title = $collection->name;
            $slug = $collection->slug;
            $video_type = $collection->taxonomy;
            $video_thumbnail = get_field('collection_image', 'term_' . $id)['url'];
            $video_image_hover = false;

            $values = array('id' => $id,'title' => $title, 'slug' => $slug, 'video_type' => $video_type, 'video_thumbnail' => $video_thumbnail, 'video_image_hover' => $video_image_hover);
            array_push($items['included'], $values);

            $args = array('post_type' => 'video', 'fields' => 'ids', 'collection' => $slug, 'numberposts' => -1);
            $exclude = get_posts($args);
            array_push($items['exclude'], ...$exclude);
        }
        return $items;
    }


    function get_collection($item){

        $line = array('id' => $item->term_id, 'line_name'=> $item->name, 'line_slug' => $item->slug,  'source' => $item->taxonomy, 'seasons' => get_field('seasons', 'collection_'. $item->term_id) );

        global $lines;
        array_push($lines, $line);

        return;
    }


    function get_custom($items){

        $line_name = get_sub_field('custom_title');
        $line_model = get_sub_field('model');

        $line = array('id' => 0, 'line_name'=> $line_name, 'line_slug' => false,  'source' => 'custom', 'model' => $line_model,  'items' => []);

        foreach ($items as $item) {

            switch ($item['acf_fc_layout']) {
                case 'collection':
                
                    $args = array('taxonomy' => 'collection',  'number' => 0, 'include' => $item['to_custom_collection']->term_id);
                    $collection = get_line_collection($args);
                    
                    if($line_model == 'circle' or $line_model == 'vertical'){ $collection['included'][0]['video_thumbnail'] = $item['image']['url']; }
                    
                    array_push($line['items'], ...$collection['included']);
                    
                break;

                case 'video':
                
                    $args = array('post_type' => 'video', 'fields' => '', 'include' => $item['video']->ID, 'numberposts' => 0);
                    $post = get_line_post($args);

                    if($line_model == 'circle'){ $post[0]['video_thumbnail'] = $item['image']['url']; }

                    array_push($line['items'], ...$post);                    
                    
                break;
            }
        }

        global $lines;
        array_push($lines, $line);

        return;
    }

    function pagination_array($items = array(), $page, $per_page){

        $page = is_null($page) ? 1 : $page;
        $per_page = is_null($per_page) ? 5 : $per_page;

        if($per_page == -1){ 
            return $items; 
        }
    
        $total = count($items);      
        $totalPages = ceil($total / $per_page ); 
        $page = max($page, 1);
        //$page = min($page, $totalPages);
        $offset = ($page - 1) * $per_page;
        if( $offset < 0 ) $offset = 0;
    
        $paged = array_slice( $items, $offset, $per_page);
    
        return array('paged' => $paged, 'totalPages' => $totalPages);
    }
    

    add_action( 'rest_api_init', 'adding_collection_meta_rest' );
    add_action( 'rest_api_init', 'adding_video_meta_rest' );

    function adding_collection_meta_rest() {
        register_rest_field( 'collection',
          'seasons',
            array(
                'get_callback'      => 'collection_meta_callback',
                'update_callback'   => null,
                'schema'            => null,
            )
        );

        register_rest_field( 'collection',
          'extras',
            array(
                'get_callback'      => 'collection_meta_callback',
                'update_callback'   => null,
                'schema'            => null,
            )
        );

        register_rest_field( 'collection',
          'link_sharing',
            array(
                'get_callback'      => 'collection_meta_callback',
                'update_callback'   => null,
                'schema'            => null,
            )
        );
    }

    function collection_meta_callback( $collection, $field_name, $request) {
        
        $id = $collection['id'];
        $values = array();
       
        switch ($field_name) {
            case 'seasons':
                $items = get_terms( 'collection', array('hide_empty' => 0, 'parent' => $id));

                foreach ($items as $key => $item) {
                    $link = 'collection/' . $collection['slug'] . '/' . $item->slug . '?s=' . $item->term_id; 
                    $items[$key]->link_sharing = get_site_url(null, $link);
                    $items[$key]->collection_image = get_field('collection_image', 'collection_' . $item->term_id)['url'];
                }

                return $items;
            break;

            case 'extras':
                $values = array(
                    'redes' => get_field('redes', 'term_' . $id),
                    'production' => get_field('producao', 'term_' . $id)
                );               
            break;

            case 'link_sharing':                
                $link = 'collection/' . $collection['slug'] . '?c=' . $id;
                $values =    get_site_url(null, $link);

            break;
        }

       return $values;
    }    

    function adding_video_meta_rest() {
        register_rest_field( 'video',
          'link_sharing',
            array(
                'get_callback'      => 'video_meta_callback',
                'update_callback'   => null,
                'schema'            => null,
            )
        );
    }

    function video_meta_callback( $video, $field_name, $request) {

        $genre = get_term($video['genre'][0], 'genre');
        $link = 'genre/' . $genre->slug . '/' . $video['slug'] . '?v=' . $video['id'];
        $link_sharing =    get_site_url(null, $link);
             
        return $link_sharing;

    }

    function echox($item){
        echo(json_encode($item));
        die;
    }   

    // Adiciona filtro p/ metas na saida rest
    // parametros:
    //      meta_key
    //      meta_value
    // exemplo: video/?meta_key=post_video_type&meta_value=Single&per_page=5&_fields=title
    add_filter( "rest_video_query", "filter_rest_video_query", 10, 2 ); 
    function filter_rest_video_query( $args, $request ) { 
        $params = $request->get_params(); 
        
        if(isset($params['meta_key']) && isset($params['meta_value'])){
            $args['meta_query'][] = array(
                array(
                    'key'     => $params['meta_key'],
                    'value'   => $params['meta_value'],
                ),
            );
        }
        return $args; 
    }

?>