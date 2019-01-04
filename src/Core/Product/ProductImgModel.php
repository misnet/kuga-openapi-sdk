<?php
namespace Kuga\Core\Product;
use Kuga\Core\Base\ModelException;
use Kuga\Core\Base\AbstractModel;

/**
 * 商品图片Model
 * Class ProductImgModel
 * @package Kuga\Core\Product
 */
class ProductImgModel extends AbstractModel {
    /**
     *
     * @var integer
     */
    public $id;
    /**
     * 商品ID
     * @var integer
     */
    public $productId;
    /**
     * 是否封面
     * @var string
     */
    public $isFirst;
    /**
     * 图网址
     * @var string
     */
    public $imgUrl;
    /**
     * 视频网址
     * @var string
     */
    public $videoUrl;


    public function getSource() {
        return 't_mall_product_imgs';
    }
    public function initialize(){
        parent::initialize();
        $this->belongsTo('productId', 'ProductModel', 'id');
    }
    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        return [
            'id' => 'id',
            'product_id'=>'productId',
            'is_first' =>'isFirst',
            'img_url' =>'imgUrl',
            'video_url' =>'videoUrl'
        ];
    }

    /**
     * 清除之后，图片与视频要清理
     */
    public function afterDelete(){
        $data = [];
        if($this->imgUrl){
            $data[] = $this->imgUrl;
        }
        if($this->videoUrl){
            $data[] = $this->videoUrl;
        }
        try {
            if (!empty($data)) {
                $queue = new \Qing\Lib\Queue();
                //$queue = $this->getDI()->getShared('queue');
                $queue->put('mediaRecycle', $data);
            }
        }catch(ModelException $e){
            //无队列处理则跳过
        }
        return true;
    }
}
