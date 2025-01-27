<?php
/**
 * 2007-2020 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

class HomeImageSlider extends ObjectModel
{
	public $title;
	public $description;
	public $url;
	public $legend;
	public $image;
	public $video;
	public $image_mobile;
	public $video_mobile;
	public $active;
	public $position;
	public $id_shop;

	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = array(
		'table' => 'ishomeslider_slides',
		'primary' => 'id_ishomeslider_slides',
		'multilang' => true,
		'fields' => array(
			'active' =>					array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
			'position' =>				array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),

			// Lang fields
			'description' =>		array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 4000),
			'title' =>					array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255),
			'legend' =>					array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255),
			'url' =>						array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isUrl', 'required' => true, 'size' => 255),
			'image' =>					array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255),
			'image_mobile' =>		array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255),
			'video' => 				array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255),
			'video_mobile' => 		array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255),
			'date_start' => 		array('type' => self::TYPE_DATE),
			'date_end' => 			array('type' => self::TYPE_DATE),
		)
	);

	public	function __construct($id_slide = null, $id_lang = null, $id_shop = null, Context $context = null)
	{
		parent::__construct($id_slide, $id_lang, $id_shop);
	}

	public function add($autodate = true, $null_values = false)
	{
		$context = Context::getContext();
		$id_shop = $context->shop->id;

		$res = parent::add($autodate, $null_values);
		$res &= Db::getInstance()->execute('
			INSERT INTO `'._DB_PREFIX_.'ishomeslider` (`id_shop`, `id_ishomeslider_slides`)
			VALUES('.(int)$id_shop.', '.(int)$this->id.')'
		);
		return $res;
	}

	public function delete()
	{
		$res = true;

		$images = $this->image;
		foreach ($images as $image)
		{
			if (preg_match('/sample/', $image) === 0)
				if ($image && file_exists(dirname(__FILE__).'/images/'.$image))
					$res &= @unlink(dirname(__FILE__).'/images/'.$image);
		}

		$images = $this->image_mobile;
		foreach ($images as $image)
		{
			if (preg_match('/sample/', $image) === 0)
				if ($image && file_exists(dirname(__FILE__).'/images/'.$image))
					$res &= @unlink(dirname(__FILE__).'/images/'.$image);
		}

		$videos = $this->video;
        foreach ($videos as $video) {
            if ($video && file_exists(__DIR__ . '/images/' . $video)) {
                $res &= @unlink(__DIR__ . '/images/' . $video);
            }
        }

		$videos = $this->video_mobile;
        foreach ($videos as $video) {
            if ($video && file_exists(__DIR__ . '/images/' . $video)) {
                $res &= @unlink(__DIR__ . '/images/' . $video);
            }
        }

		$res &= $this->reOrderPositions();

		$res &= Db::getInstance()->execute('
			DELETE FROM `'._DB_PREFIX_.'ishomeslider`
			WHERE `id_ishomeslider_slides` = '.(int)$this->id
		);

		$res &= parent::delete();
		return $res;
	}

	public function reOrderPositions()
	{
		$id_slide = $this->id;
		$context = Context::getContext();
		$id_shop = $context->shop->id;

		$max = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT MAX(hss.`position`) as position
			FROM `'._DB_PREFIX_.'ishomeslider_slides` hss, `'._DB_PREFIX_.'ishomeslider` hs
			WHERE hss.`id_ishomeslider_slides` = hs.`id_ishomeslider_slides` AND hs.`id_shop` = '.(int)$id_shop
		);

		if ((int)$max == (int)$id_slide)
			return true;

		$rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT hss.`position` as position, hss.`id_ishomeslider_slides` as id_slide
			FROM `'._DB_PREFIX_.'ishomeslider_slides` hss
			LEFT JOIN `'._DB_PREFIX_.'ishomeslider` hs ON (hss.`id_ishomeslider_slides` = hs.`id_ishomeslider_slides`)
			WHERE hs.`id_shop` = '.(int)$id_shop.' AND hss.`position` > '.(int)$this->position
		);

		foreach ($rows as $row)
		{
			$current_slide = new HomeImageSlider($row['id_slide']);
			--$current_slide->position;
			$current_slide->update();
			unset($current_slide);
		}

		return true;
	}

	public static function getAssociatedIdsShop($id_slide)
	{
		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT hs.`id_shop`
			FROM `'._DB_PREFIX_.'ishomeslider` hs
			WHERE hs.`id_ishomeslider_slides` = '.(int)$id_slide
		);

		if (!is_array($result))
			return false;

		$return = array();

		foreach ($result as $id_shop)
			$return[] = (int)$id_shop['id_shop'];

		return $return;
	}

}
