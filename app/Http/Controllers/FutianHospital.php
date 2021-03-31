<?php


namespace App\Http\Controllers;


use App\Exceptions\BaseResponseException;
use App\Http\Services\BaseService;
use App\Result;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

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
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
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
        BaseService::sc_send('有号了!');
        return Result::success();
    }

    /**
     * 科室列表
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
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
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
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
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
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
