<?php
/**
 * 加密项Model
 *
 * @author Donny
 */

namespace Kuga\Core\SecretGuard\Model;

use Kuga\Core\Base\AbstractModel;
use Kuga\Core\SecretGuard\Openssl;

class ItemModel extends AbstractModel
{

    /**
     *
     * @var integer
     */
    public $id;

    /**
     * 密码项目名称
     *
     * @var string
     */
    public $name;

    /**
     * 拥有者姓名
     *
     * @var string
     */
    public $owner;

    /**
     * 会员ID
     *
     * @var integer
     */
    public $mid;

    /**
     * 密码
     *
     * @var string
     */
    public $passwd;

    /**
     * 账户名称
     *
     * @var string
     */
    public $account;

    /**
     * 登陆网址
     *
     * @var string
     */
    public $loginUrl;

    /**
     * 摘要，备注
     *
     * @var string
     */
    public $summary;

    public $createTime;

    /**
     * 分类ID
     *
     * @var
     */
    public $cid;

    /**
     * 分类名称
     *
     * @var string
     */
    public $cname;

    /**
     * 套色方案
     *
     * @var string
     */
    public $color;

    public function getSource()
    {
        return 't_secret_items';
    }

    public function initialize()
    {
        parent::initialize();
        $this->keepSnapshots(true);
        $this->belongsTo('cid', 'BaselistModel', 'id');
    }

    public function columnMap()
    {
        return ['id'         => 'id', 'item_name' => 'name', 'item_account' => 'account', 'item_passwd' => 'passwd', 'item_summary' => 'summary',
                'item_owner' => 'owner', 'mid' => 'mid', 'item_login_url' => 'loginUrl', 'create_time' => 'createTime', 'cid' => 'cid',
                'color'      => 'color'];
    }

    public function beforeCreate()
    {
        $this->passwd = trim($this->passwd);
        $ssl          = new Openssl();
        $row          = PublicKeyModel::findFirst(
            ['mid=?1', 'bind' => [1 => $this->mid]]
        );
        if ( ! $row) {
            throw new \Exception($this->translator->_('您需要先生成密钥'));
        }
        $ssl->setPublicKeyString($row->content);
        $this->passwd = $ssl->encrypt($this->passwd);
        if ( ! $this->createTime) {
            $this->createTime = time();
        }

        return true;
    }

    public function beforeUpdate()
    {
        $this->passwd = trim($this->passwd);
        if ($this->passwd && $this->hasSnapshotData() && $this->hasChanged('passwd')) {
            $ssl = new Openssl();
            $row = PublicKeyModel::findFirst(
                ['mid=?1', 'bind' => [1 => $this->mid]]
            );
            if ( ! $row) {
                throw new \Exception($this->translator->_('您需要先生成密钥'));
            }
            $ssl->setPublicKeyString($row->content);
            $this->passwd = $ssl->encrypt($this->passwd);
        } else {
            $this->skipAttributes(['item_passwd']);

        }

        return true;
    }

}
