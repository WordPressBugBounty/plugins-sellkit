<?php

namespace Sellkit\Contact_Segmentation\Conditions;

use Sellkit\Contact_Segmentation\Conditions\Condition_Base;

defined( 'ABSPATH' ) || die();

/**
 * Class Visitor's language.
 *
 * @package Sellkit\Contact_Segmentation\Conditions
 * @since 1.1.0
 */
class Visitor_Language extends Condition_Base {

	/**
	 * Condition name.
	 *
	 * @since 1.1.0
	 */
	public function get_name() {
		return 'visitor-language';
	}

	/**
	 * Condition title.
	 *
	 * @since 1.1.0
	 */
	public function get_title() {
		return __( 'Visitor\'s Local Language', 'sellkit' );
	}

	/**
	 * Condition type.
	 *
	 * @since 1.1.0
	 */
	public function get_type() {
		return self::SELLKIT_MULTISELECT_CONDITION_VALUE;
	}


	/**
	 * It is pro feature or not.
	 *
	 * @since 1.1.0
	 */
	public function is_pro() {
		return true;
	}

	/**
	 * It searchable.
	 *
	 * @since 1.1.0
	 */
	public function is_searchable() {
		return true;
	}

	/**
	 * Gets options.
	 *
	 * @since 1.1.0
	 * @return string[]
	 */
	public function get_options() {
		$languages = [
			'ab' => 'Abkhazian',
			'aa' => 'Afar',
			'af' => 'Afrikaans',
			'ak' => 'Akan',
			'sq' => 'Albanian',
			'am' => 'Amharic',
			'ar' => 'Arabic',
			'an' => 'Aragonese',
			'hy' => 'Armenian',
			'as' => 'Assamese',
			'av' => 'Avaric',
			'ae' => 'Avestan',
			'ay' => 'Aymara',
			'az' => 'Azerbaijani',
			'bm' => 'Bambara',
			'ba' => 'Bashkir',
			'eu' => 'Basque',
			'be' => 'Belarusian',
			'bn' => 'Bengali',
			'bh' => 'Bihari languages',
			'bi' => 'Bislama',
			'bs' => 'Bosnian',
			'br' => 'Breton',
			'bg' => 'Bulgarian',
			'my' => 'Burmese',
			'ca' => 'Catalan, Valencian',
			'km' => 'Central Khmer',
			'ch' => 'Chamorro',
			'ce' => 'Chechen',
			'ny' => 'Chichewa, Chewa, Nyanja',
			'zh' => 'Chinese',
			'cu' => 'Church Slavonic, Old Bulgarian, Old Church Slavonic',
			'cv' => 'Chuvash',
			'kw' => 'Cornish',
			'co' => 'Corsican',
			'cr' => 'Cree',
			'hr' => 'Croatian',
			'cs' => 'Czech',
			'da' => 'Danish',
			'dv' => 'Divehi, Dhivehi, Maldivian',
			'nl' => 'Dutch, Flemish',
			'dz' => 'Dzongkha',
			'en' => 'English',
			'eo' => 'Esperanto',
			'et' => 'Estonian',
			'ee' => 'Ewe',
			'fo' => 'Faroese',
			'fj' => 'Fijian',
			'fi' => 'Finnish',
			'fr' => 'French',
			'ff' => 'Fulah',
			'gd' => 'Gaelic, Scottish Gaelic',
			'gl' => 'Galician',
			'lg' => 'Ganda',
			'ka' => 'Georgian',
			'de' => 'German',
			'ki' => 'Gikuyu, Kikuyu',
			'el' => 'Greek (Modern)',
			'kl' => 'Greenlandic, Kalaallisut',
			'gn' => 'Guarani',
			'gu' => 'Gujarati',
			'ht' => 'Haitian, Haitian Creole',
			'ha' => 'Hausa',
			'he' => 'Hebrew',
			'hz' => 'Herero',
			'hi' => 'Hindi',
			'ho' => 'Hiri Motu',
			'hu' => 'Hungarian',
			'is' => 'Icelandic',
			'io' => 'Ido',
			'ig' => 'Igbo',
			'id' => 'Indonesian',
			'ia' => 'Interlingua (International Auxiliary Language Association)',
			'ie' => 'Interlingue',
			'iu' => 'Inuktitut',
			'ik' => 'Inupiaq',
			'ga' => 'Irish',
			'it' => 'Italian',
			'ja' => 'Japanese',
			'jv' => 'Javanese',
			'kn' => 'Kannada',
			'kr' => 'Kanuri',
			'ks' => 'Kashmiri',
			'kk' => 'Kazakh',
			'rw' => 'Kinyarwanda',
			'kv' => 'Komi',
			'kg' => 'Kongo',
			'ko' => 'Korean',
			'kj' => 'Kwanyama, Kuanyama',
			'ku' => 'Kurdish',
			'ky' => 'Kyrgyz',
			'lo' => 'Lao',
			'la' => 'Latin',
			'lv' => 'Latvian',
			'lb' => 'Letzeburgesch, Luxembourgish',
			'li' => 'Limburgish, Limburgan, Limburger',
			'ln' => 'Lingala',
			'lt' => 'Lithuanian',
			'lu' => 'Luba-Katanga',
			'mk' => 'Macedonian',
			'mg' => 'Malagasy',
			'ms' => 'Malay',
			'ml' => 'Malayalam',
			'mt' => 'Maltese',
			'gv' => 'Manx',
			'mi' => 'Maori',
			'mr' => 'Marathi',
			'mh' => 'Marshallese',
			'ro' => 'Moldovan, Moldavian, Romanian',
			'mn' => 'Mongolian',
			'na' => 'Nauru',
			'nv' => 'Navajo, Navaho',
			'nd' => 'Northern Ndebele',
			'ng' => 'Ndonga',
			'ne' => 'Nepali',
			'se' => 'Northern Sami',
			'no' => 'Norwegian',
			'nb' => 'Norwegian Bokmål',
			'nn' => 'Norwegian Nynorsk',
			'ii' => 'Nuosu, Sichuan Yi',
			'oc' => 'Occitan (post 1500)',
			'oj' => 'Ojibwa',
			'or' => 'Oriya',
			'om' => 'Oromo',
			'os' => 'Ossetian, Ossetic',
			'pi' => 'Pali',
			'pa' => 'Panjabi, Punjabi',
			'ps' => 'Pashto, Pushto',
			'fa' => 'Persian',
			'pl' => 'Polish',
			'pt' => 'Portuguese',
			'qu' => 'Quechua',
			'rm' => 'Romansh',
			'rn' => 'Rundi',
			'ru' => 'Russian',
			'sm' => 'Samoan',
			'sg' => 'Sango',
			'sa' => 'Sanskrit',
			'sc' => 'Sardinian',
			'sr' => 'Serbian',
			'sn' => 'Shona',
			'sd' => 'Sindhi',
			'si' => 'Sinhala, Sinhalese',
			'sk' => 'Slovak',
			'sl' => 'Slovenian',
			'so' => 'Somali',
			'st' => 'Sotho, Southern',
			'nr' => 'South Ndebele',
			'es' => 'Spanish, Castilian',
			'su' => 'Sundanese',
			'sw' => 'Swahili',
			'ss' => 'Swati',
			'sv' => 'Swedish',
			'tl' => 'Tagalog',
			'ty' => 'Tahitian',
			'tg' => 'Tajik',
			'ta' => 'Tamil',
			'tt' => 'Tatar',
			'te' => 'Telugu',
			'th' => 'Thai',
			'bo' => 'Tibetan',
			'ti' => 'Tigrinya',
			'to' => 'Tonga (Tonga Islands)',
			'ts' => 'Tsonga',
			'tn' => 'Tswana',
			'tr' => 'Turkish',
			'tk' => 'Turkmen',
			'tw' => 'Twi',
			'ug' => 'Uighur, Uyghur',
			'uk' => 'Ukrainian',
			'ur' => 'Urdu',
			'uz' => 'Uzbek',
			've' => 'Venda',
			'vi' => 'Vietnamese',
			'vo' => 'Volap_k',
			'wa' => 'Walloon',
			'cy' => 'Welsh',
			'fy' => 'Western Frisian',
			'wo' => 'Wolof',
			'xh' => 'Xhosa',
			'yi' => 'Yiddish',
			'yo' => 'Yoruba',
			'za' => 'Zhuang, Chuang',
			'zu' => 'Zulu',
		];

		$input_value = sellkit_htmlspecialchars( INPUT_GET, 'input_value' );

		return sellkit_filter_array( $languages, $input_value );
	}

	/**
	 * Check if the condition is active or not.
	 *
	 * @return bool
	 */
	public function is_active() {
		return false;
	}
}
