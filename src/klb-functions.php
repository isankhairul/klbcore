<?php


use Phalcon\Di;


/**
 * @param null $alias
 *
 * @return mixed
 */
if ( !function_exists( 'di' ) ) {
    function di( $alias = null )
    {
        $default = Di::getDefault();

        if ( is_string( $alias ) ) {
            return $default->get( $alias );
        }

        # if the alias is array then we must check the array
        # passed in
        if ( is_array( $alias ) ) {
            if (
                !isset( $alias[0] ) ||
                !isset( $alias[1] )
            ) {
                throw new InvalidArgumentException( 'Provider alias or callback not found' );
            }

            $default->set(
                $alias[0],
                $alias[1],
                isset( $alias[2] ) ? $alias[2] : false
            );

            return $default->get( $alias[0] );
        }

        # or just return the default thing
        return $default;
    }
}

if ( !function_exists( 'admin_uri' ) ) {
    /**
     * @return string
     */
    function admin_uri()
    {
        return 'home';
    }
}
if ( !function_exists( 'api_uri' ) ) {
    /**
     * @return string
     */
    function api_uri()
    {
        return 'api/v1';
    }
}
if ( !function_exists( 'is_admin' ) ) {
    /**
     * @param null $auth
     *
     * @return bool
     */
    function is_admin( $auth = null )
    {
        if ( !$auth ) {
            $auth = di()->auth;
        }

        return $auth->isAdmin();
    }
}
if ( !function_exists( 'clean_input' ) ) {
    /**
     * @param       $moneys
     * @param array $in
     *
     * @return array
     */
    function clean_input( $moneys, array $in = [] )
    {
        $in[] = '_token';
        $in[] = 'task';

        return array_filter( $moneys, function ( $k ) use ( $in ) {
            return substr( $k, 0, 5 ) !== 'dist-' && !in_array( $k, $in );
        }, ARRAY_FILTER_USE_KEY );
    }
}
if ( !function_exists( 'normal_case' ) ) {
    /**
     * @param $value
     *
     * @return string
     */
    function normal_case( $value )
    {
        return ucwords( str_replace( [ '-', '_' ], ' ', $value ) );
    }
}
if ( !function_exists( 'umask_money' ) ) {
    /**
     * @param $decimal
     *
     * @return mixed
     */
    function umask_money( $decimal )
    {
        if ( is_numeric( $decimal ) || $decimal === '' ) return $decimal;
        if ( strpos( $decimal, 'Rp' ) !== false ) $decimal = str_replace( 'Rp', '', $decimal );
        if ( strpos( $decimal, ',00' ) !== false ) $decimal = str_replace( ',00', '', $decimal );
        if ( strpos( $decimal, ' ' ) !== false ) $decimal = str_replace( ' ', '', $decimal );
        if ( strpos( $decimal, '.' ) !== false ) $decimal = str_replace( '.', '', $decimal );
        if ( $decimal === '' ) return null;

        return $decimal;
    }
}

if ( !function_exists( 'pre' ) ) {
    /**
     *
     */
    function pre()
    {
        if ( !defined( 'STDIN' ) ) {
            echo '<pre>';
        } else {
            echo PHP_EOL;
        }
        foreach ( func_get_args() as $arg )
            print_r( $arg );
        if ( !defined( 'STDIN' ) ) {
            echo '</pre>';
        } else {
            echo PHP_EOL;
        }
        exit;
    }
}

if ( !function_exists( 'currency' ) ) {
    /**
     * @param      $value
     * @param bool $withHtml
     * @param bool $negative
     * @param null $label
     *
     * @return string
     */
    function currency( $value, $withHtml = true, $negative = false, $label = null )
    {
        $currency = '';
        $valueFormat = number_format( $value, 2, ',', '.' );
        if ( $negative === true ) {
            $valueFormat = '-' . $valueFormat;
        }
        if ( false === $withHtml ) {
            $currency = 'Rp';

            return $currency . ' ' . $valueFormat;
        }

        if ( $label !== null ) {
            $currency = 'Rp';

            return <<<HTML
<div class="font-grey-mint font-sm"> $label </div>
<div class="uppercase font-hg font-red-flamingo"> $currency
    <span class="font-lg font-grey-mint">$valueFormat</span>
</div>
HTML;
        }

        return '<span class="currency-rp">' . $currency . '</span>' . $valueFormat;
    }
}

if ( !function_exists( 'pad_currency' ) ) {
    /**
     * @param $value
     * @param $pad_length
     *
     * @return string
     */
    function pad_currency( $value, $pad_length )
    {
        $currency = 'Rp ';
        $valueFormat = number_format( $value, 2, ',', '.' );

        return $currency . str_pad( $valueFormat . '  ', $pad_length - strlen( $currency ), ' ', STR_PAD_LEFT );
    }
}

if ( !function_exists( 'date_to_sql' ) ) {
    /**
     * @param $date
     *
     * @return bool|string
     */
    function date_to_sql( $date )
    {
        $time = strtotime( $date );
        if ( $time === false ) {
            if ( strpos( $date, '/' ) !== false ) {
                list( $d, $m, $y ) = explode( '/', $date );
                $date = "$y-$m-$d";
            } else if ( strlen( $date ) === 8 ) {
                $d = substr( $date, 0, 2 );
                $m = substr( $date, 2, 2 );
                $y = substr( $date, 4, 4 );
                $date = "$y-$m-$d";
            } else if ( strlen( $date ) === 19 ) {
                $d = substr( $date, 0, 2 );
                $m = substr( $date, 2, 2 );
                $y = substr( $date, 4, 4 );
                $h = substr( $date, 11, 2 );
                $i = substr( $date, 14, 2 );
                $s = substr( $date, 17, 2 );
                $date = "$y-$m-$d $h:$i:$s";
            }
        } else {
            $date = date( 'Y-m-d H:i:s', $time );
        }

        return $date;
    }
}

if ( !function_exists( 'txt_pad' ) ) {
    /**
     * @param        $config
     * @param        $str
     * @param        $index
     * @param string $sep
     *
     * @return string
     */
    function txt_pad( $config, $str, $index, $sep = ' ' )
    {
        if ( !isset( $config[$index] ) ) {
            return $str;
        }
        $lengthIndexes = $config[$index];
        $result = str_pad( $str, $lengthIndexes, $sep, STR_PAD_RIGHT );

        return $result;
    }
}

if ( !function_exists( 'format_number' ) ) {
    /**
     * @param     $number
     * @param int $decimal
     *
     * @return string
     */
    function format_number( $number, $decimal = 2 )
    {
        return number_format( $number, $decimal, ',', '.' );
    }
}
if ( !function_exists( 'format_qty' ) ) {
    /**
     * @param $number
     *
     * @return string
     */
    function format_qty( $number, $isProp = 0, $round = false )
    {
        if ( $isProp == 1 ) {
            return format_number( $number, 4 );
        }
        if ( $round === true ) {
            $number = ceil( $number );
        }

        return is_float( $number ) ? $number : (int) $number;
    }
}

if ( !function_exists( 'format_percent' ) ) {
    /**
     * @param $number
     *
     * @return string
     */
    function format_percent( $number )
    {
        return number_format( $number, 2, ',', '.' );
    }
}

if ( !function_exists( 'format_datetime' ) ) {
    /**
     * @param        $date
     * @param bool   $time
     * @param string $default
     *
     * @return bool|string
     */
    function format_datetime( $date, $time = true, $default = '-' )
    {
        $format = 'd M Y';

        if ( $time ) {
            $format = 'd M Y h:i A';
        }

        if ( !is_date( $date ) ) {
            return $default;
        }

        if ( !is_integer( $date ) ) {
            $date = strtotime( $date );
        }

        return date( $format, $date );
    }
}

if ( !function_exists( 'humanize' ) ) {

    /**
     * @param $date
     *
     * @return null|string
     */
    function humanize( $date )
    {
        if ( !is_date( $date ) ) {
            return null;
        }
        try {
            return \KlbV2\Core\PrettyDateTime::parse( new DateTime( $date ) );
        } catch ( Exception $e ) {

        }

        return null;
    }
}

if ( !function_exists( 'display_date' ) ) {
    /**
     * @param        $date
     * @param bool   $time
     * @param string $default
     *
     * @return bool|string
     */
    function display_date( $date, $time = true, $default = '-' )
    {
        $format = 'd M Y';

        if ( $time ) {
            $format = 'd M Y H:i';
        }

        if ( !is_date( $date ) ) {
            return $default;
        }

        if ( !is_integer( $date ) ) {
            $date = strtotime( $date );
        }

        return date( $format, $date );
    }
}

if ( !function_exists( 'is_date' ) ) {
    /**
     * @param $date
     *
     * @return bool
     */
    function is_date( $date )
    {
        if ( !is_numeric( $date ) ) {
            $date = strtotime( $date );
        }

        if ( $date <= 0 || false === $date || strpos( $date, '0000-00-00' ) !== false || strpos( $date, '1970-01-01' ) !== false ) {
            return false;
        }

        return true;
    }
}

if ( !function_exists( 'a_format_date' ) ) {
    /**
     * @param        $date
     * @param string $format
     *
     * @return bool|string
     */
    function a_format_date( $date, $format = 'Ymd' )
    {
        if ( !is_numeric( $date ) ) {
            $date = strtotime( $date );
        }
        if ( !is_date( $date ) ) {
            return '';
        }

        return date( $format, $date );
    }
}

if ( !function_exists( 'a_format_date_id_sql' ) ) {
    /**
     * @param $date
     *
     * @return bool|string
     */
    function a_format_date_id_sql( $date )
    {
        $parseDate = a_format_date( $date, 'Y-m-d' );
        if ( $parseDate === '' ) {
            $explode = explode( '/', $date );

            return $explode[2] . '-' . $explode[1] . '-' . $explode[0];
        }

        return $parseDate;
    }
}

if ( !function_exists( 'slug' ) ) {
    /**
     * @param        $string
     * @param array  $replace
     * @param string $delimiter
     *
     * @return mixed|string
     */
    function slug( $string, array $replace = [], $delimiter = '-' )
    {
        if ( !empty( $replace ) ) {
            $string = str_replace( array_keys( $replace ), array_values( $replace ), $string );
        }
        // replace non letter or non digits by -
        $string = preg_replace( '#[^\pL\d]+#u', '-', $string );
        // Trim trailing -
        $string = trim( $string, '-' );
        $clean = preg_replace( '~[^-\w]+~', '', $string );
        $clean = strtolower( $clean );
        $clean = preg_replace( '#[\/_|+ -]+#', $delimiter, $clean );
        $clean = trim( $clean, $delimiter );

        return $clean;
    }
}

if ( !function_exists( 'clean_ascii' ) ) {
    /**
     * @param       $string
     * @param array $replace
     *
     * @return mixed|string
     * @throws Exception
     */
    function clean_ascii( $string, array $replace = [] )
    {
        if ( !extension_loaded( 'iconv' ) or !extension_loaded( 'mbstring' ) ) {
            throw new Exception( 'iconv or mbstring module not loaded' );
        }
        // Save the old locale and set the new locale to UTF-8
        $oldLocale = setlocale( LC_ALL, '0' );
        setlocale( LC_ALL, 'en_US.UTF-8' );
        $string = !mb_detect_encoding( $string, 'UTF-8', true ) ? utf8_encode( $string ) : $string;
        $clean = iconv( 'UTF-8', 'ASCII//TRANSLIT', $string );
        if ( !empty( $replace ) ) {
            $clean = str_replace( array_keys( $replace ), array_values( $replace ), $clean );
        }
//        $clean = preg_replace("/[^a-zA-Z0-9\/_\.,\(\)|+ -]/", '', $clean);
        $clean = trim( $clean );
        // Revert back to the old locale
        setlocale( LC_ALL, $oldLocale );

        return $clean;
    }
}

if ( !function_exists( 'trim_sku' ) ) {
    /**
     * @param $sku
     *
     * @return mixed|string
     * @throws Exception
     */
    function trim_sku( $sku )
    {
        if ( is_ascii( $sku ) ) {
            return clean_ascii( $sku, [ "'" => "", "|" => "" ] );
        }
        $sku = str_replace( "'", "", $sku );
        $sku = str_replace( "|", "", $sku );

        return trim( $sku );
    }
}

if ( !function_exists( 'is_ascii' ) ) {
    /**
     * Checks if a string is 7 bit ASCII.
     *
     * @param string $str <p>The string to check.</p>
     *
     * @return bool <p>
     *              <strong>true</strong> if it is ASCII<br />
     *              <strong>false</strong> otherwise
     *              </p>
     */
    function is_ascii( $str )
    {
        $str = (string) $str;
        if ( !isset( $str[0] ) ) {
            return true;
        }

        return (bool) !preg_match( '/[\x80-\xFF]/', $str );
    }
}

if ( !function_exists( 'weekly_sunday' ) ) {
    /**
     * @param        $start
     * @param        $end
     * @param string $format
     *
     * @return array
     */
    function weekly_sunday( $start, $end, $format = 'Y-m-d' )
    {
        $timestamp1 = strtotime( $start );
        $timestamp2 = strtotime( $end );
        $sundays = [];
        $oneDay = 60 * 60 * 24;

        for ( $i = $timestamp1; $i <= $timestamp2; $i += $oneDay ) {
            $day = date( 'N', $i );

            // If sunday
            if ( $day == 7 ) {
                // Save sunday in format YYYY-MM-DD, if you need just timestamp
                // save only $i
                $sundays[] = date( $format, $i );

                // Since we know it is sunday, we can simply skip
                // next 6 days so we get right to next sunday
                $i += 6 * $oneDay;
            }
        }

        return $sundays;
    }
}

if ( !function_exists( 'current_weekly_sunday' ) ) {
    /**
     * @param string $format
     *
     * @return array
     */
    function current_weekly_sunday( $format = 'Y-m-d' )
    {
        $day = date( 'w' );
        $week_start = date( 'Y-m-d', strtotime( '-' . $day . ' days' ) );
        $week_end = date( 'Y-m-d', strtotime( '+' . ( 7 - $day ) . ' days' ) );
//        var_dump(compact('week_start', 'week_end'), weekly_sunday($week_start, $week_end, $format));
        $weekly = weekly_sunday( $week_start, $week_end, $format );

        return end( $weekly );
    }
}

if ( !function_exists( 'date_weekly_sunday' ) ) {
    /**
     * @param        $date
     * @param string $format
     *
     * @return mixed
     */
    function date_weekly_sunday( $date, $format = 'Y-m-d' )
    {

        if ( is_integer( $date ) ) {
            $time = $date;
        } else {
            $time = strtotime( $date );
        }
        $day = date( 'w', $time );
        $week_start = date( 'Y-m-d', strtotime( '-' . $day . ' days', $time ) );
        $week_end = date( 'Y-m-d', strtotime( '+' . ( 7 - $day ) . ' days', $time ) );
        $weekly = weekly_sunday( $week_start, $week_end, $format );
        if ( (int) $day === 0 ) {
            $period = current( $weekly );
        } else {
            $period = end( $weekly );
        }

        return $period;
    }
}

if ( !function_exists( 'print_attribute' ) ) {
    /**
     * @param $attributes
     *
     * @return string
     */
    function print_attribute( $attributes )
    {
        if ( !is_array( $attributes ) ) {
            return '';
        }
        $attr = "";
        foreach ( $attributes as $attrKey => $attrVal ) {
            if ( !empty( $attrVal ) ) {
                $attr .= " $attrKey=\"$attrVal\"";
            } else {
                $attr .= " $attrKey";
            }
        }

        return trim( $attr );
    }
}

if ( !function_exists( 'isJSON' ) ) {
    /**
     * @param $string
     *
     * @return array|bool|mixed
     */
    function isJSON( $string )
    {
        $decode = json_decode( $string, true );

        return is_string( $string ) && is_array( $decode ) && ( json_last_error() == JSON_ERROR_NONE ) ? $decode : false;
    }
}

if ( !function_exists( 'cp' ) ) {
    /**
     * To copy a certain source to destination.
     *
     * @param string $source The source file/folder
     * @param string $dest   The destination file/folder
     *
     * @return void
     */
    function cp( $source, $dest )
    {
        if ( is_dir( $dest ) === false ) {
            mkdir( $dest, 0755, true );
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $source,
                RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ( $iterator as $item ) {
            # check if the item is directory
            if ( $item->isDir() ) {
                # check if there is existing directory
                # else create.

                $_temp_dir = $dest . '/' . $iterator->getSubPathName();

                if ( is_dir( $_temp_dir ) === false ) {
                    mkdir( $dest . '/' . $iterator->getSubPathName(), true );
                }

                continue;
            }

            # it is a file
            copy( $item, $dest . '/' . $iterator->getSubPathName() );
        }
    }
}

if ( !function_exists( 'folder_files' ) ) {
    /**
     * Get all the files from assigned path.
     *
     * @param string $path The path to be iterated
     *
     * @return mixed
     */
    function folder_files( $path, $sub_dir = false )
    {
        if ( file_exists( $path ) === false ) {
            return [];
        }

        $iterator = new RecursiveDirectoryIterator(
            $path,
            RecursiveDirectoryIterator::SKIP_DOTS
        );

        $files = [];

        foreach ( $iterator as $item ) {
            if ( $item->isDir() ) {
                if ( $sub_dir === true ) {
                    $tmp_files = folder_files( $item->getPathName(), true );
                    $files = array_merge( $files, $tmp_files );
                }

                continue;
            }

            $files[] = $item->getPathName();
        }

        return $files;
    }
}

if ( !function_exists( 'clean_title' ) ) {
    /**
     * @param $string
     *
     * @return string
     */
    function clean_title( $string )
    {
        $result = '';
        if ( $string ) {
            $string = str_replace( '_', ' ', $string );
            $result = ucwords( strtolower( $string ) );
        }

        return $result;
    }
}

if ( !function_exists( 'get_dir_upload_path' ) ) {
    /**
     * @param $filename
     *
     * @return string
     */
    function get_dir_upload_path( $filename )
    {
        $pathInfo = pathinfo( $filename );
        $fileName = $pathInfo['filename'];
        $split = preg_split( '/[\s-_]/', $fileName );
        $dir = [];
        $i = 0;
        foreach ( $split as $item ) {
            $dir[$item[0]] = $item[0];
            $i++;
            if ( $i > 3 ) {
                break;
            }
        }

        return strtolower( join( DIRECTORY_SEPARATOR, $dir ) );
    }
}

if ( !function_exists( 'recursive_rmdir' ) ) {
    /**
     * @param $dir
     */
    function recursive_rmdir( $dir )
    {
        $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
        foreach ( $iterator as $filename => $fileInfo ) {
            if ( $fileInfo->isDir() ) {
                rmdir( $filename );
            } else if ( '.' !== substr( $fileInfo->getFilename(), 0, 1 ) ) {
                unlink( $filename );
            }
        }
    }
}

if ( !function_exists( 'recursive_rmfile' ) ) {
    /**
     * @param       $dir
     * @param array $extension
     */
    function recursive_rmfile( $dir, array $extension = [] )
    {
        $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
        $countExt = count( $extension );
        foreach ( $iterator as $filename => $fileInfo ) {
            if ( !$fileInfo->isDir() && '.' !== substr( $fileInfo->getFilename(), 0, 1 ) && ( $countExt === 0 || ( $countExt > 0 && in_array( $fileInfo->getExtension(), $extension ) ) ) ) {
                unlink( $filename );
            }
        }
    }
}

if ( !function_exists( 'recursive_copy_file' ) ) {
    /**
     * @param       $dir
     * @param       $target
     * @param array $extension
     */
    function recursive_copy_file( $dir, $target, array $extension = [] )
    {
        $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
        $countExt = count( $extension );
        foreach ( $iterator as $filename => $fileInfo ) {
            if ( !$fileInfo->isDir() && '.' !== substr( $fileInfo->getFilename(), 0, 1 ) && ( $countExt === 0 || ( $countExt > 0 && in_array( $fileInfo->getExtension(), $extension ) ) ) ) {
                copy( $filename, $target . DIRECTORY_SEPARATOR . $fileInfo->getFilename() );
            }
        }
    }
}

if ( !function_exists( 'booleanAsInt' ) ) {
    /**
     * @param $value
     *
     * @return int
     */
    function booleanAsInt( $value )
    {
        if ( is_numeric( $value ) ) {
            return (int) $value > 0 ? 1 : 0;
        }
        if ( $value === true ) {
            return 1;
        }
        if ( in_array( $value, [ 'y', 'Yes', 'Y', 'Enabled' ] ) ) {
            return 1;
        }

        return 0;
    }
}

if ( !function_exists( 'is_image_remote' ) ) {
    /**
     * @param $url
     *
     * @return bool
     */
    function is_image_remote( $url )
    {
        $header = @get_headers( $url, 1 );
        if ( !$header ) {
            return false;
        }
        $headers = array_change_key_case( $header );

        if ( is_array( $headers ['content-type'] ) ) {
            $headers ['content-type'] = current( $headers ['content-type'] );
        }

        return substr( $headers ['content-type'], 0, 5 ) === 'image' || $headers['content-type'] === 'application/pdf';
    }
}


if ( !function_exists( 'slugify' ) ) {
    /**
     * @param $str
     *
     * @return array|string|string[]|null
     */
    function slugify( $str )
    {
        // Convert to lowercase and remove whitespace
        $str = strtolower( trim( $str ) );

        // Replace high ascii characters
        $chars = [ "ä", "ö", "ü", "ß" ];
        $replacements = [ "ae", "oe", "ue", "ss" ];
        $str = str_replace( $chars, $replacements, $str );
        $pattern = [ "/(é|è|ë|ê)/", "/(ó|ò|ö|ô)/", "/(ú|ù|ü|û)/" ];
        $replacements = [ "e", "o", "u" ];
        $str = preg_replace( $pattern, $replacements, $str );

        // Remove puncuation
        $pattern = [ ":", "!", "?", ".", "/", "'" ];
        $str = str_replace( $pattern, "", $str );

        // Hyphenate any non alphanumeric characters
        $pattern = [ "/[^a-z0-9-]/", "/-+/" ];
        $str = preg_replace( $pattern, "_", $str );

        return $str;
    }
}

if ( !function_exists( 'capitalize_first' ) ) {
    function capitalize_first( $string )
    {
        return ucfirst( strtolower( $string ) );
    }
}

