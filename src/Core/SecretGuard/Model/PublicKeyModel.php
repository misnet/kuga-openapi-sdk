<?php
/**
 * 公钥库，用于加密用
 *
 * @author Donny
 *
 */

namespace Kuga\Core\SecretGuard\Model;

use Kuga\Core\Base\AbstractModel;
use Kuga\Core\Base\ModelException;

class PublicKeyModel extends AbstractModel
{

    /**
     *
     * @var integer
     */
    public $id;

    /**
     * 会员ID
     *
     * @var integer
     */
    public $mid;

    /**
     * 公钥内容
     *
     * @var string
     */
    public $content;

    public $isPfxfileRemoved;

    public function getSource()
    {
        return 't_secret_publickeys';
    }

    public function initialize()
    {
        parent::initialize();
        $this->keepSnapshots(true);
    }

    public function columnMap()
    {
        return ['id' => 'id', 'content' => 'content', 'mid' => 'mid', 'is_pfxfile_removed' => 'isPfxfileRemoved'];
    }

    public function beforeCreate()
    {
        $num = $this->count(
            ['mid=?1', 'bind' => [1 => $this->mid]]
        );
        if ($num > 0) {
            throw new ModelException($this->translator->_('对不起，一个用户只能创建一对密钥'));
        }

        return true;
    }
}
