<?php


namespace App\Http\Controllers;


use App\Exceptions\BaseResponseException;
use App\Http\Services\BaseService;
use App\Result;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

class FutianHospital extends Controller
{
    // 科室查询  http://def.szftzy.com/szftzyy/rest/mu004

    // 医生查询  http://def.szftzy.com/szftzyy/rest/mu037
    // 参数 deptId=74

    // 挂号剩余数查询 http://def.szftzy.com/szftzyy/rest/mu011
    // 参数 context=1&openId=oCtVwuE0SLlfKphbP_tLA-YXKeEg&deptId=74&doctorId=256&startDate=2021-03-27&endDate=2021-03-27

    // 查询就诊人信息 http://def.szftzy.com/szftzyy/rest/chaxunJiuzhenrenXinxi
    // 参数 context=IEfzNDR46nR5X6b5EwEMTuBgKgntSg1MG6o3DGIUfL3bFOj59vO6gyOL%252BijVG3GSLqeyv8TUvNhP3tFIDqYUZA%253D%253D&openId=DbSe5QeyEuM4JC9SGwyn9UTHxvDS1UFCzNFVjCM17lF2WW69%252BDRdqRtYPDuBYmOMsGQmH%252FcbxX2Kc4SEfsLIDw%253D%253D

    /**
     * 基础请求
     * @param $method
     * @param $uri
     * @param array $data
     * @return mixed|ResponseInterface
     * @throws GuzzleException
     */
    public static function baseRequest($method, $uri, $data = [])
    {
        $baseUrl = 'http://def.szftzy.com';
        $client = new Client([
            'base_uri' => $baseUrl
        ]);
        try {
            $response = $client->request($method, $uri, $data);
        } catch (ClientException $exception) {
            throw new BaseResponseException('请求失败：'.$exception->getMessage());
        }
        $response = json_decode($response->getBody(), true);
        return $response;
    }

    /**
     * 自动循环刷号
     */
    public function registered()
    {
        set_time_limit(0); //设置执行最长时间，0为无限制。

        $doctorIds = request('doctor_ids', []);
        $startDate = request('start_date');
        $endDate = request('end_date');
        $deptId = request('dept_id');
        $fee = request('fee', 0);
        if (!$startDate || !$endDate || !$deptId || empty($doctorIds)) {
            throw new BaseResponseException('自动刷号请求 参数错误');
        }

        $autoMachine = function ($doctorIds, $startDate, $endDate, $deptId, $fee) {
            $dateArr = [];
            while (true) {
                $dateArr[] = $startDate;
                if ($startDate == $endDate) {
                    break;
                }
                $startDate = date('Y-m-d', strtotime($startDate) + 24 * 60 * 60);
            }

            $flag = 0;
            foreach ($dateArr as $date) {
                foreach ($doctorIds as $doctorId) {
                    $data = [
                        'form_params' => [
                            'context' => 1,
                            'openId' => 'oCtVwuE0SLlfKphbP_tLA-YXKeEg',
                            'deptId' => $deptId,
                            'doctorId' => $doctorId,
                            'startDate' => $date,
                            'endDate' => $date
                        ]
                    ];
                    try {
                        Log::info('请求参数:', $data);
                        $response = self::baseRequest('POST', '/szftzyy/rest/mu011', $data);
                        if (is_array($response) && $response['success'] == true && !empty($response['obj'])) {
                            Log::info('响应：', $response);
                            foreach ($response['obj'] as $obj) {
                                if ($fee == 0 || $obj['treatFee'] <= $fee) {
                                    foreach ($obj['Mu012'] as $item) {
                                        if ($item['regLeaveCount'] > 0) {
                                            $deptName = $obj['deptName'];
                                            $doctorName = $obj['doctorName'];
                                            $doctorDec = $obj['doctorTitle'];
                                            $dateTime = $obj['regDate']. ' '. $item['startTime']. '-'. $item['endTime'];
                                            $regLeaveCount = $item['regLeaveCount'];
                                            $treatFee = $obj['treatFee'];

                                            $title = '深圳福田中医院';
                                            $desp = <<<XML
## 科室
$deptName
## 医生
$doctorName
> $doctorDec

## 时间
$dateTime
## 余号数量
$regLeaveCount
## 挂号费
$treatFee
XML;

                                            Log::info('发送企业微信：'.$desp);
                                            BaseService::sc_send($title, $desp);

                                            ++$flag;
                                        }
                                    }
                                }
                            }
                        }
                    } catch (Exception $exception) {
                        continue;
                    }
                }
            }
            if ($flag > 0) {
                Cache::forget('auto_registered_key');
            }
        };

        Cache::forever('auto_registered_key', 1);
        while (true) {
            if (!Cache::get('auto_registered_key')) {
                break;
            }

            $autoMachine($doctorIds, $startDate, $endDate, $deptId, $fee);
        }

        return Result::success();
    }

    /**
     * 停止刷号
     * @return ResponseFactory|Response
     */
    public function stopAutoReg()
    {
        Cache::forget('auto_registered_key');
        return Result::success();
    }

    /**
     * 科室列表
     * @return ResponseFactory|Response
     * @throws GuzzleException
     */
    public function getDepartmentList()
    {
        $response = self::baseRequest('POST', '/szftzyy/rest/mu004');
        if ($response['success'] === true) {
            return Result::success($response['obj']['dataset']['row']);
        } else {
            throw new BaseResponseException('返回科室列表错误');
        }
    }

    /**
     * 获取医生列表
     * @return ResponseFactory|Response
     * @throws GuzzleException
     */
    public function getDoctor()
    {
        $deptId = request('dept_id');
        if (!$deptId) {
            throw new BaseResponseException('dept_id科室id不能为空');
        }
        $data = [
            'form_params' => [
                'deptId' => $deptId
            ]
        ];
        $response = self::baseRequest('POST', '/szftzyy/rest/mu037', $data);
        if ($response['success'] === true) {
            return Result::success($response['obj']['dataset']['row']);
        } else {
            throw new BaseResponseException('返回医生列表错误');
        }
    }

    /**
     * 获取就诊时间和剩余挂号数量
     * @return ResponseFactory|Response
     * @throws GuzzleException
     */
    public function getTreatmentTimeAndRemainingTimes()
    {
        // context=1&openId=oCtVwuE0SLlfKphbP_tLA-YXKeEg&deptId=74&doctorId=256&startDate=2021-03-27&endDate=2021-03-27
        $startDate = request('start_date');
        $endDate = request('end_date');
        $deptId = request('dept_id');
        $doctorId = request('doctor_id');
        if (!$startDate || !$endDate || !$deptId || !$doctorId) {
            throw new BaseResponseException('请求就诊时间和挂号剩余次数参数错误');
        }
        $data = [
            'form_params' => [
                'context' => 1,
                'openId' => 'oCtVwuE0SLlfKphbP_tLA-YXKeEg',
                'deptId' => $deptId,
                'doctorId' => $doctorId,
                'startDate' => $startDate,
                'endDate' => $endDate
            ]
        ];
        $response = self::baseRequest('POST', '/szftzyy/rest/mu011', $data);
        return Result::success($response);
    }
}
