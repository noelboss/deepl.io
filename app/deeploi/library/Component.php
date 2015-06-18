<?php

include_once( 'Utils.php' );
include_once( 'User.php' );

// Path to the default component template files
define( 'COMPONENTTEMPLATE', BASE . 'app/terrific/templates/module/' );

class Component {

	private $config; // component config object
	private $names; // object with component name(s)
	private $skins; // object with skin name(s)
	private $user; // user class
	private $utils; // utils class
	private $placeholders; // object with placeholder that are substituted in templates
	private $headless = false; // no html output if true
	private $markup = ''; // collected markup for output

	/**
	 * @param null $config
	 * @param null $name
	 * @param null $skin
	 * @param null $user
	 * @param null $email
	 */
	function __construct( $config = null, $name = null, $skin = null, $user = null, $email = null ) {

		if ( !is_array( $_SERVER ) ) {
			$this->headless = true;
		}

		$this->config = $config;

		$this->user  = new User( $user, $email );
		$this->utils = new Utils();

		$this->names = $this->getComponentNames( $name );
		$this->skins = $this->getComponentNames( $skin );

		foreach ( $this->names as $component ) {
			if ( !empty( $component ) ) {
				$this->createComponent( $component );
			}
		}

		if ( !$this->headless ) {
			$this->utils->htmlFragmentStart( 'Create Component' );

			echo '<h1>Create ' . ucfirst( $this->config->component ) . '</h1>';
			echo '<div class="row"><div class="col-md-6">';
			echo $this->getHtmlForm();
			echo '</div><div class="col-md-6">';
			echo $this->markup;
			echo '</div></div>';

			$this->utils->htmlFragmentEnd( $this->getScriptBlock() );
		}
	}

	/**
	 * @param $string
	 *
	 * @return array
	 */
	private function getComponentNames( $string ) {

		// remove double space & dash
		$string = preg_replace( '/\s+/', ' ', trim( $string ) );
		$string = preg_replace( '/-+/', '-', $string );

		// remove all special characters (allow -, a-z, 0-9 & space)
		$string = preg_replace( '/[^-a-z0-9\s]+/i', '', $string );

		$names = explode( ' ', $string );

		// remove invalid names
		$names = array_filter( $names, function ( $name ) {
			return preg_match( '/^[a-z]/i', $name ) === 1;
		} );

		return array_unique( $names );
	}

	/**
	 * @param $name
	 *
	 * @return string
	 */
	private function getCssName( $name ) {
		$name = str_replace( ' ', '-', trim( preg_replace( '/([A-Z])/', ' $0', $name ) ) );

		return strtolower( $name );
	}

	/**
	 * @param $name
	 *
	 * @return string
	 */
	private function getJsName( $name ) {
		$name = preg_replace_callback( '/\-(.)/', create_function( '$matches', 'return strtoupper($matches[1]);' ), $name );

		return ucwords( $name );
	}

	/**
	 * get configured component prefix
	 *
	 * @return string
	 */
	private function getComponentPrefix() {
		return property_exists($this->config, 'component_prefix') ? $this->config->component_prefix : '';
	}

	/**
	 * get configured skin prefix
	 *
	 * @return string
	 */
	private function getSkinPrefix() {
		return property_exists($this->config, 'skin_prefix') ? $this->config->skin_prefix : '';
	}

	/**
	 * create files for component
	 * test component:   Navigation NavMain Nav      service navService   nav-support nav--help BBB bär 4GoLD !$$g  n$k
	 * test skin:   blue OrAnge yel-low
	 *
	 * @param $componentName
	 */
	private function createComponent( $componentName ) {

		$hasSkins           = count( $this->skins ) > 0;
		$componentDirectory = BASE . $this->config->path . '/' . $componentName;
		$templateDirectory  = BASE . $this->config->template;

		$this->fillPlaceholders( $componentName );

		$this->addMarkup( '<div class="panel panel-primary">' );
		$this->addMarkup( '<div class="panel-heading">' . ucfirst( $this->config->component ) . ' : ' . $componentName . '</div>' );
		$this->addMarkup( '<div class="panel-body">' );

		if ( !is_dir( $componentDirectory ) ) {
			mkdir( $componentDirectory, 0755, true );
			$this->addMarkup( '<div class="alert alert-success">Created</div>' );
		}
		else {
			$this->addMarkup( '<div class="alert alert-success">Updated</div>' );
		}

		if ( $hasSkins ) {
			$this->addMarkup( '<p>Skins: ' );
			foreach ( $this->skins as $skin ) {
				if ( !empty( $skin ) ) {
					$this->addMarkup( '<span> ' . $skin . '</span> ' );
				}
			}
			$this->addMarkup( '</p>' );
		}

		$this->addMarkup( '</div></div>' );

		foreach ( $this->glob_recursive( $templateDirectory . '/*' ) as $templateFile ) {

			$filePart = str_replace( $templateDirectory . '/', '', $templateFile );

			if ( !is_dir( $templateFile ) ) {

				if ( !$hasSkins && strpos( $filePart, '_skin' ) !== false ) { // we have no skins -> we do not process files with skin in name
					continue;
				}

				$filePart = strtolower( str_replace( '_component', $componentName, $filePart ) ); // replace 'component' with lowercase component name

				if ( $hasSkins && strpos( $filePart, '_skin' ) !== false ) { // we have sinks and file contains skin
					foreach ( $this->skins as $skin ) {
						if ( !empty( $skin ) ) {
							$this->fillSkinPlaceholders( $skin );
							$filePartSkin = strtolower( str_replace( '_skin', $skin, $filePart ) ); // replace 'skin' with lowercase skin name
							$newFile      = $componentDirectory . '/' . $filePartSkin;
							$this->createFile( $templateFile, $newFile );
						}
					}
					continue;
				}
			}

			$newFile = $componentDirectory . '/' . $filePart;
			$this->createFile( $templateFile, $newFile );
		}

	}

	/**
	 * creates a file or directory
	 * (and replaces placholders)
	 *
	 * @param $templateFile
	 * @param $newFile
	 */
	private function createFile( $templateFile, $newFile ) {
		try {
			if ( is_dir( $templateFile ) ) {
				if ( !is_dir( $newFile ) ) {
					mkdir( $newFile, 0755, true );
				}
			}
			else {
				if ( !is_file( $newFile ) ) {
					$templateCode = file_get_contents( $templateFile );
					if ( $templateCode !== false && !file_exists( $newFile ) ) {
						foreach ( $this->placeholders as $search => $replace ) {
							$templateCode = str_replace( $search, $replace, $templateCode );
						}
						file_put_contents( $newFile, $templateCode );
					}
				}
			}
		} catch ( Exception $e ) {
		}
	}


	/**
	 * fill placeholders for current component
	 *
	 * @param $componentName
	 */
	private function fillPlaceholders( $componentName ) {
		$this->placeholders = array(
			'{{component}}'        => $componentName,
			'{{component-css}}'    => $this->getCssName( $componentName ),
			'{{component-js}}'     => $this->getJsName( $componentName ),
			'{{component-file}}'   => strtolower( $componentName ),
			'{{component-id}}'     => strtolower( $this->getJsName( $componentName ) ),
			'{{component-prefix}}' => $this->getComponentPrefix(),
			'{{user}}'             => $this->user->getName(),
			'{{email}}'            => $this->user->getEmail()
		);
	}

	/**
	 * fill additonal placeholders for current skin
	 *
	 * @param $skinName
	 */
	private function fillSkinPlaceholders( $skinName ) {
		$this->placeholders['{{skin}}']        = $skinName;
		$this->placeholders['{{skin-css}}']    = $this->getCssName( $skinName );
		$this->placeholders['{{skin-js}}']     = $this->getJsName( $skinName );
		$this->placeholders['{{skin-file}}']   = strtolower( $skinName );
		$this->placeholders['{{skin-id}}']     = strtolower( $this->getJsName( $skinName ) );
		$this->placeholders['{{skin-prefix}}'] = $this->getSkinPrefix();
	}

	/**
	 * get the whole form markup
	 *
	 * @return string
	 */
	private function getHtmlForm() {

		global $config;

		$markup = '';
		$markup .= '
			<form action="#" method="post" accept-charset="utf-8">
				<div class="form-group">
					<label class="control-label" for="component">' . ucfirst( $this->config->component ) . ':</label>
					<input type="text" id="component" name="component" class="form-control" autocomplete="off" placeholder="' . ucfirst( $this->config->component ) . ' ' . ucfirst( $this->config->component ) . 'Two" value="" />

					<p class="js-existing" style="margin-top: .5em;">';

		foreach ( $this->glob_recursive( BASE . $this->config->path . '/*', GLOB_ONLYDIR ) as $dir ) {
			$component = basename( $dir );
			if ( is_file( $dir . '/' . strtolower( $component ) . '.' . $config->micro->view_file_extension ) || is_dir( $dir . '/css' ) || is_dir( $dir . '/js' ) ) {
				// this is a component
				$markup .= '<a title="Existing component: ' . $component . '" href="#' . $component . '" class="label label-default js-component">' . $component . '</a> ';
			}
		}

		$markup .= '
					</p>
				</div>';


		if ( property_exists( $this->config, 'skin_prefix' ) ) {
		$markup .= '
				<div class="form-group">
					<label class="control-label" for="skin">Skin:</label>
					<input type="text" id="skin" name="skin" class="form-control" placeholder="Skin SkinTwo" value="" />
				</div>';
		}

		$markup .= '
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label class="control-label" for="user">Username:</label>
							<input type="text" id="user" name="user" class="form-control" value="' . $this->user->getName() . '" />
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label class="control-label" for="email">E-Mail:</label>
							<input type="text" id="email" name="email" class="form-control" value="' . $this->user->getEmail() . '" />
						</div>
					</div>
				</div>
				<div class="form-group">
					<button type="submit" class="btn btn-primary btn-lg">Create</button>
				</div>
			</form>';

		return $markup;
	}


	/**
	 * get the js behaviour for the page
	 * @return string
	 */
	private function getScriptBlock() {
		$script = <<<'SCRIPTBLOCK'
			$(document).ready(function(){
				$(document).on('click', '.js-component', function(e){
					e.preventDefault();

					var $input = $('#component'),
						$this = $(this);

					$input.val($input.val()+' '+$this.text());
					if($this.siblings('.js-component').length < 1){
						$('.js-existing').fadeOut();
					}
					$this.remove();
				});
			});
SCRIPTBLOCK;

		return $script;
	}

	/**
	 * collect markup from generation
	 *
	 * @param $string
	 */
	private function addMarkup( $string ) {
		$this->markup .= $string;
	}

	/**
	 * does not support flag GLOB_BRACE
	 *
	 * @param $pattern
	 * @param int $flags
	 *
	 * @return array
	 */
	private function glob_recursive( $pattern, $flags = 0 ) {
		$files = glob( $pattern, $flags );
		foreach ( glob( dirname( $pattern ) . '/*', GLOB_ONLYDIR | GLOB_NOSORT ) as $dir ) {
			$files = array_merge( $files, $this->glob_recursive( $dir . '/' . basename( $pattern ), $flags ) );
		}

		return $files;
	}

}
