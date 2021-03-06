<?php

namespace App\Http\Controllers;

use App\GoodCategoriesModel;
use App\GoodConfigureModel;
use App\GoodModel;
use App\HostModel;
use App\Http\Controllers\Payment\PayController;
use App\Http\Controllers\Server\ServerPluginController;
use App\NewModel;
use App\OrderModel;
use App\ServerModel;
use App\SettingModel;
use App\User;
use App\WorkOrderModel;
use App\WorkOrderReplyModel;
use Carbon\Carbon;
use Faker\Provider\Payment;
use Illuminate\Http\Request;

/**
 *  * 管理页面以及全局设置页面及操作
 * TODO 清理代码结构
 * Class AdminController
 * @package App\Http\Controllers
 */
class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('check.admin.authority');
    }

    /**
     * 一个简单的确认管理员权限
     * @param $user
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|null
     */
    public static function checkAdminAuthority($user)
    {
        if ($user) {
            if ($user->admin_authority) {
                return null;
            };
            return redirect('/home');
        }
        return redirect('/login');
    }

    /**\
     * 返回管理员页面首页
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexPage()
    {
        $userCount = User::all()->count();
        $orderCount = OrderModel::all()->count();
        $workOrderCount = WorkOrderModel::all()->count();
        $hostCount = HostModel::all()->count();
        $servers = $this->getServers();
        $orders = [];
        for ($i =1;$i<=7;$i++){
            $start = Carbon::now()->subDays($i-1);
            $end = Carbon::now()->subDay($i);
            $order = OrderModel::where([
               ['created_at','<',$start],
               ['created_at','>',$end]
            ])->get();
            $order ?? null;
            array_push($orders,$order);
        }
        return view(ThemeController::backAdminThemePath('index'), compact('userCount', 'orderCount','orders', 'workOrderCount', 'hostCount','servers'));
    }

    /**
     * 获取商品配置
     */
    protected function getGoodsConfigure()
    {
        $goodsConfigure = GoodConfigureModel::where([
            ['status', '!=', '0']
        ])->get();
        !$goodsConfigure->isEmpty() ?: $goods = null;
        return $goodsConfigure;
    }

    /**
     * 获取商品
     */
    protected function getGoods()
    {
        $goods = GoodModel::where([
            ['status', '!=', '0']
        ])->get();
        !$goods->isEmpty() ?: $goods = null;
        return $goods;
    }

    /**
     * 获取商品分类
     */
    protected function getGoodsCategories()
    {
        $goods_categories = GoodCategoriesModel::where([
            ['status', '!=', '0']
        ])->get();
        !$goods_categories->isEmpty() ?: $goods_categories = null;
        return $goods_categories;
    }

    /**
     * 全局设置首页
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function settingIndexPage()
    {
        $setting = SettingModel::all();
        $themes = ThemeController::getThemeArr();
        $adminThemes = ThemeController::getAdminThemeArr();
        $payPlugins = PayController::getPayPluginArr();
        return view(ThemeController::backAdminThemePath('index', 'setting'), compact('setting', 'themes', 'payPlugins', 'adminThemes'));
    }

    /**
     * 获取服务器
     */
    protected function getServers()
    {
        $servers = ServerModel::where([
            ['status', '!=', '0']
        ])->get();
        !$servers->isEmpty() ?: $servers = null;
        return $servers;
    }

    /**
     * 获取主机
     */
    protected function getHosts()
    {
        $hosts = HostModel::where([
            ['status', '!=', '0']
        ])->orderBy('created_at', 'desc')
            ->paginate(10);
        !$hosts->isEmpty() ?: $hosts = null;
        return $hosts;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * 返回商品列表
     */
    public function goodShowPage()
    {
        //获取商品信息
        $goods = $this->getGoods();
        $goods_categories = $this->getGoodsCategories();
        $goodsConfigure = $this->getGoodsConfigure();
        return view(ThemeController::backAdminThemePath('show', 'goods'), compact('goods', 'goods_categories', 'goodsConfigure'));
    }


    /**
     * 添加商品
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function goodAddPage()
    {
        $servers = $this->getServers();
        $goodsConfigure = $this->getGoodsConfigure();
        $goods_categories = $this->getGoodsCategories();
        return view(ThemeController::backAdminThemePath('add', 'goods'), compact('goodsConfigure', 'goods_categories', 'servers'));
    }

    /**
     * 添加商品分类页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function goodCategoriesAddPage()
    {
        return view(ThemeController::backAdminThemePath('add_categories', 'goods'));
    }

    /**
     * 添加商品配置页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function goodConfigureAddPage()
    {

        return view(ThemeController::backAdminThemePath('add_configure', 'goods'));
    }

    /**
     * 编辑商品分类页面
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function goodCategoriesEditPage($id)
    {
        $categories = GoodCategoriesModel::where('id', $id)->get();
        if (!$categories->isEmpty()) {
            $categories = $categories->first();
            return view(ThemeController::backAdminThemePath('edit_categories', 'goods'), compact('categories'));
        }
        return redirect(route('admin.good.show')); //错误返回
    }

    /**
     * 编辑商品分类页面
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function goodConfigureEditPage($id)
    {
        $configure = GoodConfigureModel::where('id', $id)->get();
        if (!$configure->isEmpty()) {
            $configure = $configure->first();
            return view(ThemeController::backAdminThemePath('edit_configure', 'goods'), compact('configure'));
        }
        return redirect(route('admin.good.show')); //错误返回
    }

    /**
     * 编辑商品页面
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function goodEditPage($id)
    {
        $servers = $this->getServers();
        $goodsConfigure = $this->getGoodsConfigure();
        $goods_categories = $this->getGoodsCategories();
        $goods = GoodModel::where('id', $id)->get();
        if (!$goods->isEmpty()) {
            $goods = $goods->first();
            return view(ThemeController::backAdminThemePath('edit', 'goods'), compact('goods', 'servers', 'goodsConfigure', 'goods_categories'));
        }
        return redirect(route('admin.good.show')); //错误返回
    }

    /**
     * 编辑新闻页面
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function newEditAction($id)
    {
        $new = NewModel::where('id', $id)->get();
        if (!$new->isEmpty()) {
            $new = $new->first();
            return view(ThemeController::backAdminThemePath('edit', 'news'), compact('new'));
        }
        return redirect(route('admin.good.show')); //错误返回
    }

    /**
     * 返回服务器管理页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function serverShowPage()
    {
        $servers = $this->getServers();
        return view(ThemeController::backAdminThemePath('show', 'servers'), compact('servers'));
    }

    /**
     * 返回添加服务器页面
     */
    public function serverAddPage()
    {
        $serverPlugin = ServerPluginController::getServerPluginArr();
        return view(ThemeController::backAdminThemePath('add', 'servers'), compact('serverPlugin'));
    }

    /**
     * 工单详细以及回复
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function workOrderDetailedPage($id)
    {
        $workOrder = WorkOrderModel::where('id', $id)->get();
        if (!$workOrder->isEmpty()) {
            $workOrder = $workOrder->first();
            $reply = WorkOrderReplyModel::where('work_order_id', $workOrder->id)->get();
            !$reply->isEmpty() ?: $reply = null;//防止报错
            return view(ThemeController::backAdminThemePath('detailed', 'work_order'), compact('workOrder', 'reply'));
        }
        return redirect(route('admin.work.order.show')); //错误返回
    }

    /**
     * 返回服务器编辑页面
     */
    public function serverEditPage($id)
    {
        $serverPlugin = ServerPluginController::getServerPluginArr();
        $server = ServerModel::where('id', $id)->get();
        if (!$server->isEmpty()) {
            $server = $server->first();
            return view(ThemeController::backAdminThemePath('edit', 'servers'), compact('serverPlugin', 'server'));
        }
        return redirect(route('admin.good.show')); //错误返回
    }

    /**
     * 用户列表
     */
    public function userShowPage()
    {
        $users = User::where([
            ['status', '!=', '0']
        ])
            ->orderBy('created_at', 'desc')
            ->paginate(10);;
        return view(ThemeController::backAdminThemePath('show', 'users'), compact('users'));
    }

    /**
     * 用户编辑页面
     */
    public function userEditPage($id)
    {
        $user = User::where('id', $id)->get();
        if (!$user->isEmpty()) {
            $user = $user->first();
            return view(ThemeController::backAdminThemePath('edit', 'users'), compact('user'));
        }
        return redirect(route('admin.good.show')); //错误返回

    }
    /**
     * 订单编辑页面
     */
    public function orderEditPage($no)
    {
        $order = OrderModel::where('no', $no)->get();
        if (!$order->isEmpty()) {
            $order = $order->first();
            return view(ThemeController::backAdminThemePath('detailed', 'orders'), compact('order'));
        }
        return redirect(route('admin.order.show')); //错误返回

    }

    /**
     *订单列表页面
     */
    public function orderShowPage()
    {
        $orders = OrderModel::where([
            ['status', '!=', '0']
        ])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view(ThemeController::backAdminThemePath('show', 'orders'), compact('orders'));
    }

    /**
     * 新建新闻页面
     */
    public function newAddPage()
    {
        return view(ThemeController::backAdminThemePath('add', 'news'));
    }

    /**
     * 工单列表页面
     */
    public function workOrderShowPage()
    {
        $workOrder = WorkOrderModel::where([
            ['status', '!=', '0']
        ])
            ->orderBy('created_at', 'desc')
            ->paginate(10);;
        return view(ThemeController::backAdminThemePath('show', 'work_order'), compact('workOrder'));
    }

    /**
     * 新闻列表页面
     */
    public function newShowPage()
    {
        $news = NewModel::where([
            ['status', '!=', '0']
        ])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view(ThemeController::backAdminThemePath('show', 'news'), compact('news'));
    }

    /**
     * 主机列表页面
     */
    public function hostShowPage()
    {
        $hosts = $this->getHosts();
        return view(ThemeController::backAdminThemePath('show', 'hosts'), compact('hosts'));
    }


    /**
     * 编辑网站配置操作
     */
    public function settingEditAction(Request $request)
    {
        //TODO 验证支付以及主题插件是否存在
//        $themes = ThemeController::getThemeArr();
//        $adminThemes = ThemeController::getAdminThemeArr();
//        $payPlugins = PayController::getPayPluginArr();


        $this->validate($request, [
            'wechatplugin' => 'string|nullable',
            'alipayplugin' => 'string|nullable',
            'admintheme' => 'string|nullable',
            'theme' => 'string|nullable',
            'title' => 'string|nullable|min:1|max:200',
            'kfurl' => 'string|nullable|min:1|max:200',
            'subtitle' => 'string|nullable|min:1|max:200',
            'copyright' => 'string|nullable|min:1|max:200',
            'currencyunit' => 'string|nullable|min:1|max:200',
            'logo' => 'string|nullable|min:1|max:200',
            'logourl' => 'string|nullable|min:1|max:200',
            'smtpurl' => 'string|nullable|min:1|max:200',
            'smtpport' => 'nullable|min:1|max:200|integer',
            'smtpuser' => 'string|nullable|min:1|max:200',
            'smtppassowrd' => 'string|nullable|min:1|max:200',
        ]);

        //TODO 垃圾代码
        SettingModel::where('name', 'setting.website.title')->first()->value == $request['title'] ?: SettingModel::where('name', 'setting.website.title')->update(['value' => $request['title']]);
        SettingModel::where('name', 'setting.website.subtitle')->first()->value == $request['subtitle'] ?: SettingModel::where('name', 'setting.website.subtitle')->update(['value' => $request['subtitle']]);
        SettingModel::where('name', 'setting.website.copyright')->first()->value == $request['copyright'] ?: SettingModel::where('name', 'setting.website.copyright')->update(['value' => $request['copyright']]);
        SettingModel::where('name', 'setting.website.logo')->first()->value == $request['logo'] ?: SettingModel::where('name', 'setting.website.logo')->update(['value' => $request['logo']]);
        SettingModel::where('name', 'setting.website.logo.url')->first()->value == $request['logourl'] ?: SettingModel::where('name', 'setting.website.logo.url')->update(['value' => $request['logourl']]);
        SettingModel::where('name', 'setting.mail.smtp.passowrd')->first()->value == $request['smtppassowrd'] ?: SettingModel::where('name', 'setting.mail.smtp.passowrd')->update(['value' => $request['smtppassowrd']]);
        SettingModel::where('name', 'setting.mail.smtp.user')->first()->value == $request['smtpuser'] ?: SettingModel::where('name', 'setting.mail.smtp.user')->update(['value' => $request['smtpuser']]);
        SettingModel::where('name', 'setting.mail.smtp.port')->first()->value == $request['smtpport'] ?: SettingModel::where('name', 'setting.mail.smtp.port')->update(['value' => $request['smtpport']]);
        SettingModel::where('name', 'setting.mail.smtp.url')->first()->value == $request['smtpurl'] ?: SettingModel::where('name', 'setting.mail.smtp.url')->update(['value' => $request['smtpurl']]);
        SettingModel::where('name', 'setting.website.payment.wechat')->first()->value == $request['wechatplugin'] ?: SettingModel::where('name', 'setting.website.payment.wechat')->update(['value' => $request['wechatplugin']]);
        SettingModel::where('name', 'setting.website.payment.alipay')->first()->value == $request['alipayplugin'] ?: SettingModel::where('name', 'setting.website.payment.alipay')->update(['value' => $request['alipayplugin']]);
        SettingModel::where('name', 'setting.website.admin.theme')->first()->value == $request['admintheme'] ?: SettingModel::where('name', 'setting.website.admin.theme')->update(['value' => $request['admintheme']]);
        SettingModel::where('name', 'setting.website.theme')->first()->value == $request['theme'] ?: SettingModel::where('name', 'setting.website.theme')->update(['value' => $request['theme']]);
        SettingModel::where('name', 'setting.website.kf.url')->first()->value == $request['kfurl'] ?: SettingModel::where('name', 'setting.website.kf.url')->update(['value' => $request['kfurl']]);
        return redirect(route('admin.setting.index'));
    }


    /**
     * 获取插件表单
     * @param $payment string alipay|wechat 支付方式
     * @return bool|mixed
     */
    protected function getPayPluginInputForm($payment)
    {
        $payment = htmlspecialchars(trim($payment));
        switch ($payment) {
            case "wechat":
                $payPlugin = SettingModel::where('name', 'setting.website.payment.wechat')->first();
                break;
            case "alipay":
                $payPlugin = SettingModel::where('name', 'setting.website.payment.alipay')->first();
                break;
            default :
                return false;
        }

        if (empty($payPlugin['value'])) {
            return false;
        }
        $pay = new PayController();
        $form = $pay->pluginConfigInputForm($payPlugin['value'], $payment);
        return $form;
    }

    /**
     * 插件配置页面
     */
    public function paymentPluginConfigPage($payment)
    {
        $form = $this->getPayPluginInputForm($payment);
        if (!empty($form)) {
            $setting = SettingModel::all();
            return view(ThemeController::backAdminThemePath('pay', 'setting'), compact('form', 'setting', 'payment'));
        }
        return redirect(route('admin.setting.index'));
    }

    /**
     * 插件配置页面
     */
    public function paymentPluginConfigAction(Request $request)
    {
        $form = $this->getPayPluginInputForm($request['payment']);
        if (empty($form)) {
            return redirect(route('admin.setting.index'));
        }
        foreach ($form as $key => $value) {
            $this->validate($request, [
                $key => 'string|nullable'
            ]);
            SettingModel::where('name', $value)->update(['value' => $request[$key]]);
        }
        return back();
    }
}
