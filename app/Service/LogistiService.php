<?php


namespace App\Service;

use App\Traits\Res;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class LogistiService {

    use Res;

    public $cred;


    public function __construct() {
        $this->cred = [
            'companyName' => env('LOGISTI_USERNAME'),
            'password' => env('LOGISTI_PASSWORD')
        ];
    }


    public $errorsCodes =  [
        ['code' => 0, 'message' => 'Internal system error, please retry later'],
        ['code' => 2, 'message' => 'NotFound - لم يتم العثور على المندوب أو الطلب'],
        ['code' => 5, 'message' => 'InvalidCredential - اسم المستخدم أو كلمة المرور خاطئة'],
        ['code' => 7, 'message' => 'IdentityTypeIdRequired - نوع الهوية مطلوب'],
        ['code' => 8, 'message' => 'IdNumberRequired - رقم الهوية مطلوب'],
        ['code' => 9, 'message' => 'DateOfBirthRequired - تاريخ الميلاد مطلوب'],
        ['code' => 10, 'message' => 'RegistrationDateRequired - تاريخ التسجيل مطلوب'],
        ['code' => 11, 'message' => 'MobileRequired - رقم الجوال مطلوب'],
        ['code' => 12, 'message' => 'RegionIdRequired - رقم المنطقة مطلوب'],
        ['code' => 13, 'message' => 'CityIdRequired - رقم المدينة مطلوب'],
        ['code' => 14, 'message' => 'CarTypeRequired - نوع المركبة مطلوب'],
        ['code' => 15, 'message' => 'CarNumberRequired - رقم اللوحة مطلوب'],
        ['code' => 16, 'message' => 'InvalidNationalityId - الجنسية غير صحيحة'],
        ['code' => 17, 'message' => 'InvalidIdentityTypeId - نوع الهوية غير صحيح'],
        ['code' => 18, 'message' => 'InvalidRegionId - رقم المنطقة غير صحيح'],
        ['code' => 19, 'message' => 'InvalidCityId - رقم المدينة غير صحيح'],
        ['code' => 20, 'message' => 'InvalidIdNumber - رقم الهوية غير صحيح'],
        ['code' => 21, 'message' => 'InvalidDriverId - رقم المندوب غير صحيح'],
        ['code' => 22, 'message' => 'CityDoesntBelongToRegion - المدينة لا تنتمي للمنطقة'],
        ['code' => 23, 'message' => 'NumberRequired - رقم الطلب حقل إجباري'],
        ['code' => 24, 'message' => 'AuthorityIdRequired - رقم الجهة مطلوب'],
        ['code' => 25, 'message' => 'CategoryIdRequired - رقم تصنيف الطلب مطلوب'],
        ['code' => 26, 'message' => 'DeliveryTimeRequired - وقت التوصيل المتوقع مطلوب'],
        ['code' => 27, 'message' => 'InvalidAuthorityId - رقم الجهة غير صحيح'],
        ['code' => 28, 'message' => 'InvalidCategoryId - رقم تصنيف الطلب غير صحيح'],
        ['code' => 29, 'message' => 'InvalidOrderId - لم يتم العثور على الطلب'],
        ['code' => 36, 'message' => 'EmptyEntries - جميع الحقول المطلوبة فارغة'],
        ['code' => 37, 'message' => 'InvalidCancellationReasonId - رقم سبب الإلغاء غير صحيح'],
        ['code' => 38, 'message' => 'CoordinatesRequired - الإحداثيات مطلوبة'],
        ['code' => 39, 'message' => 'PaymentMethodIdRequired - رقم وسيلة الدفع مطلوبة'],
        ['code' => 40, 'message' => 'PriceRequired - سعر الطلب مطلوب'],
        ['code' => 42, 'message' => 'InvalidPaymentMethodId - رقم وسيلة الدفع غير صحيح'],
        ['code' => 44, 'message' => 'MoiInvalidIdentity - المندوب لديه مشكلة في مركز المعلومات الوطني'],
        ['code' => 45, 'message' => 'InvalidCarTypeId - رقم نوع المركبة غير صحيح'],
        ['code' => 47, 'message' => 'DriverAlreadyExist - المندوب مسجل بالفعل'],
        ['code' => 49, 'message' => 'NationalityAndIdNumberAndIdentityTypeC - ال يمكن تعديل نوع الهوية أو رقم الهوية أو الجنسية'],
        ['code' => 50, 'message' => 'StoretNamedRequired - اسم المتجر مطلوب'],
        ['code' => 51, 'message' => 'StoreLocationRequired - موقع المتجر مطلوب'],
        ['code' => 52, 'message' => 'OrderCannotBeAccepted - لا يمكن قبول الطلب لأن حالته ليست جديدة'],
        ['code' => 53, 'message' => 'OrderCannotBeCanceled - لا يمكن إلغاء الطلب لأنه ملغي بالفعل أو منفذ أو مرفوض'],
        ['code' => 54, 'message' => 'OrderDidnotAcceptedYet - يجب أن يتم قبول الطلب أولاً'],
        ['code' => 55, 'message' => 'UpdateDriveryAddressCannotBeDone - لا يمكن تعديل الطلب بعد تنفيذه بالفعل'],
        ['code' => 56, 'message' => 'RefrenceCodeRequired - الرقم المرجعي للمندوب أو الطلب مطلوب'],
        ['code' => 57, 'message' => 'DriverMustBeAssignedFirst - يجب أولاً تعيين مندوب على الطلب'],
        ['code' => 58, 'message' => 'OrderNumberAlreadyCreatedToday - رقم الطلب موجود بالفعل لهذا اليوم'],
        ['code' => 59, 'message' => 'InvalidOrderNumber - رقم الطلب غير مقبول'],
        ['code' => 60, 'message' => 'InvalidMobileNumberMustStartWith05And10Digits - رقم الجوال يجب أن يتكون من 10 خانات'],
        ['code' => 61, 'message' => 'DateOfBirthDoesntMatchNICRecords - تاريخ الميلاد لا يطابق البيانات الموجودة في مركز المعلومات الوطني'],
        ['code' => 65, 'message' => 'Digits8DateOfBirthMustBe - تاريخ الميالد يجب أن يتكون من 8 خانات'],
        ['code' => 66, 'message' => 'OrderDateRequired - تاريخ الطلب مطلوب'],
        ['code' => 67, 'message' => 'AcceptanceDateWrong - تاريخ القبول غير صحيح'],
        ['code' => 68, 'message' => 'ExcutionTimeWrong - تاريخ تنفيذ الطلب غير صحيح'],
        ['code' => 70, 'message' => 'OderCanNotBeExecutedMoreThanOnce - تم تنفيذ الطلب بالفعل'],
        ['code' => 71, 'message' => 'لا يمكن تنفيذ الطلب بينما هو ملغى او مرفوض'],
        ['code' => 72, 'message' => 'ال يمكن تعيين مندوب على طلب والطلب حالته ليست مقبول'],
        ['code' => 73, 'message' => 'تاريخ تنفيذ الطلب ال يمكن أن يكون قبل تاريخ الطلب نفسه'],
        ['code' => 74, 'message' => 'المندوب معين بالفعل على هذا الطلب'],
        ['code' => 75, 'message' => 'تاريخ التنفيذ يجب أن يكون أكبر من تاريخ تعيين المندوب '],
        ['code' => 77, 'message' => 'ال يمكن أن يتم رفض الطلب ألنه مرفوض بالفعل أو تم الغاءه او تم تنفيذه'],
        ['code' => 79, 'message' => 'تاريخ الموافقة على الطلب يجب أن يكون بعد تاريخ الطلب نفسه'],
        ['code' => 80, 'message' => 'IdNUmberExpired - بطاقة الهوية أو الإقامة منتهية'],
        ['code' => 81, 'message' => 'InvalidBirthdateFormat - صيغة تاريخ الميلاد المدخل غير مقبولة'],
        ['code' => 82, 'message' => 'Driveryoungerthan18 - عمر المندوب أقل من 18 سنة بالتالي لا يمكن تسجيله'],
        ['code' => 83, 'message' => 'Active19Covid - المندوب مصاب بكورونا كوفيد 19'],
        ['code' => 84, 'message' => 'DriverNotHealthy - المندوب غير لائق طبياً'],
        ['code' => 85, 'message' => 'VehicleSequenceNumberRequired - الرقم التسلسلي للمركبة مطلوب'],
        ['code' => 86, 'message' => 'InvalidVehicleSequenceNumber - الرقم التسلسلي للمركبة غير صحيح'],
        ['code' => 87, 'message' => 'VehicleLicenseIsExpired - استمارة المركبة منتهية'],
        ['code' => 88, 'message' => 'VehicleMVPIIsExpired - الفحص الدوري للمركبة منتهي'],
        ['code' => 89, 'message' => 'DriverIsNotAuthorizedForVehicle - لمندوب ليس مالك المركبة وليس المستخدم الفعلي للمركبة وليس مفوضا عليها'],
        ['code' => 90, 'message' => 'DrivingLicenseIsExpired - رخصة القيادة منتهية'],
        ['code' => 91, 'message' => 'DriverIsAccompanying - المندوب مرافق'],
        ['code' => 92, 'message' => 'ProhibitedOccupation - مهنة المندوب محظورة للعمل حسب متطلبات الهيئة'],
        ['code' => 93, 'message' => 'OrderHasBeenClosed - تم تجميد حالة الطلب للفوترة ولا يمكن تحديثه'],
        ['code' => 94, 'message' => 'NotVaccinatedAgainstCovid19 - حالة المندوب غير محصن من فايروس كورونا كوفيد 19'],
        ['code' => 95, 'message' => 'DriverReachedMaximumOrdersPerDay - المندوب وصل للحد الأعلى من الطلبات المستلمة لليوم الواحد'],
        ['code' => 96, 'message' => 'DriverCannotDeliverInTwoRegions - لا يمكن للمندوب أن يوصل طلبات في مناطق مختلفة خلال مدة زمنية أقل من X ساعة'],
        ['code' => 97, 'message' => 'DriverHasActiveOrderAssignedInAnotherApp - المندوب يقوم بتوصيل طلب في تطبيق آخر حاليا'],
        ['code' => 98, 'message' => 'DriverIsDeactivatedByApp - المندوب معطل من قبل التطبيق'],
        ['code' => 99, 'message' => 'InvalidOrderBulkFileTemplate - الملف المرفوع غير صالح'],
        ['code' => 100, 'message' => 'InvalidOrderBulkUUID - الرقم المرجعي للملف غير صحيح'],
        ['code' => 101, 'message' => 'OrderBulkIsDisabled - رفع الملفات معطل'],
        ['code' => 102, 'message' => 'OrderBulkIsDisabledForYourApp - رفع الملفات لحسابكم معطل'],
        ['code' => 103, 'message' => 'IllegalNumberOfRows - عدد الصفوف في الملف غير صحيح'],
        ['code' => 104, 'message' => 'StillProcessingOrderBulk - الملف تحت المعالجة'],
        ['code' => 105, 'message' => 'IncorrectMobileNumberFormat - رقم جوال المستفيد غير صحيح'],
        ['code' => 106, 'message' => 'IncorrectPriceFormat - صيغة سعر الطلب أو سعر التوصيل أو المبلغ العائد للسائق غير صحيح'],
        ['code' => 107, 'message' => 'EmailRequired - البريد الإلكتروني مطلوب'],
        ['code' => 108, 'message' => 'IncorrectEmailFormat - صيغة البريد الإلكتروني غير صحيحة'],
        ['code' => 109, 'message' => 'TechnicalAndResponsibleNamesRequired - اسم المسؤول مطلوب'],
        ['code' => 110, 'message' => 'ThereIsNoContactInfoForThisApp - لا يوجد بيانات تواصل للتطبيق'],
        ['code' => 112, 'message' => 'NoOperationCard - لا يوجد بطاقة تشغيل للمركبة'],
        ['code' => 113, 'message' => 'NoActiveOperationCard - بطاقة تشغيل المركبة غير نشطة'],
        ['code' => 114, 'message' => 'ViolatedAllowedDistance - المسافة بين المتجر وموقع التوصيل تتجاوز المسافة المسموحة'],
        ['code' => 115, 'message' => 'InvalidCountryCode - رمز الدولة غير صحيح'],
        ['code' => 116, 'message' => 'InvalidCoordinates - إحداثيات التوصيل غير صحيحة'],
        ['code' => 117, 'message' => 'InvalidStoreLocation - إحداثيات المتجر غير صحيحة'],
        ['code' => 118, 'message' => 'NoActivityLicense - لا يوجد ترخيص لمنشأة السائق'],
        ['code' => 119, 'message' => 'NoActiveAcivityLicense - ترخيص منشأة السائق غير نشط'],
        ['code' => 121, 'message' => 'FaceVerificationIsRequired - مطلوب التحقق من الوجه للسائق'],
        ['code' => 123, 'message' => 'DriverSuspendedByTGA - السائق موقوف من قبل الهيئة العامة للنقل '],
        ['code' => 124, 'message' => 'DriverHasNoVehicle - السائق لا يملك مركبة'],
    ];

    public function logistiError($response) {
        if($response['status'] == false) {
            if($response['errorCodes']) {
                foreach($response['errorCodes'] as $errorCode) {
                    $codeObj = array_filter($this->errorsCodes, function($error) use($errorCode) {
                        return $error['code'] == $errorCode;
                    });
                    if($codeObj) {
                        $filtered = array_values($codeObj);
                        $message = 'error logisti';
                        if(count($filtered) > 0) {
                            $message =  $filtered[0]['message'];
                        }
                        return $this->sendRes($message, false, [], [$message], 400);
                    }
                }
            }
            return $this->sendRes('logisti errors apis with codes:', false, [], $response['errorCodes'], 400);
        }
    }


    public function create_driver($driver) {

        $url = env('LOGISTI_URL') . 'api/Driver/create';

        $data = [
            'credential' => [
                'companyName' => $this->cred['companyName'],
                'password' => $this->cred['password']
            ],
            'driver' => $driver,
        ];

        $response = Http::post($url, $data);
        return $response;
    }


    public function deActivate($idNumber) {
        $data = [
            'credential' => $this->cred,
            'idNumber' => $idNumber
        ];
        $response = Http::post(env('LOGISTI_URL') . 'api/Driver/deActivate', $data);
        return $response;
    }

    public function identity_types() {
        $response = Http::post(env('LOGISTI_URL') . 'api/Lookup/identity-types-list', $this->cred);
        return $response;
    }


    public function regions() {
        $response = Http::post(env('LOGISTI_URL') . 'api/Lookup/regions-list', $this->cred);
        return $response;
    }

    public function cities($regionId) {
        $data = [
            'credential' => $this->cred,
            'regionId' => $regionId
        ];
        $response = Http::post(env('LOGISTI_URL') . 'api/Lookup/cities-list', $data);
        return $response;
    }

    public function car_types() {
        $response = Http::post(env('LOGISTI_URL') . 'api/Lookup/car-types-list', $this->cred);
        return $response;
    }


    public function createOrder($data) {

        $url = env('LOGISTI_URL') . 'api/Order/create';
        $data = [
            'credential' => [
                'companyName' => $this->cred['companyName'],
                'password' => $this->cred['password']
            ],
            'order' => [
                'orderNumber' => $data['orderNumber'],
                'authorityId' => 'NV25GlPuOnQ=',
                'deliveryTime' => Carbon::now(),
                'regionId' => 'NV25GlPuOnQ=',
                'cityId' => 'NV25GlPuOnQ=',
                'coordinates' => $data['coordinates'],
                'storetName' => $data['storetName'],
                'storeLocation' => $data['storeLocation'],
                'categoryId' => 'NV25GlPuOnQ=',
                'orderDate' => Carbon::now(),
                'recipientMobileNumber' => $data['recipientMobileNumber']
            ],
        ];

        $response = Http::post($url, $data);
        return $response;
    }

    public function acceptOrder($orderNumber) {

        $url = env('LOGISTI_URL') . 'api/Order/accept';
        $data = [
            'credential' => [
                'companyName' => $this->cred['companyName'],
                'password' => $this->cred['password']
            ],
            'referenceCode' => $orderNumber,
            'acceptanceDateTime' => Carbon::now()
        ];

        $response = Http::post($url, $data);
        return $response;
    }

    public function assignOrder($orderNumber, $idNumber) {

        $url = env('LOGISTI_URL') . 'api/Order/assign-driver-to-order';
        $data = [
            'credential' => [
                'companyName' => $this->cred['companyName'],
                'password' => $this->cred['password']
            ],
            'referenceCode' => $orderNumber,
            'idNumber' => $idNumber
        ];

        $response = Http::post($url, $data);
        return $response;
    }

    public function execute_order($data) {

        $url = env('LOGISTI_URL') . 'api/Order/assign-driver-to-order';
        $data = [
            'credential' => [
                'companyName' => $this->cred['companyName'],
                'password' => $this->cred['password']
            ],
            'orderExecutionData' => [
                'referenceCode' => $data['referenceCode'],
                'executionTime' => $data['executionTime'],
                'paymentMethodId' => $data['paymentMethodId'],
                'price' => $data['price'],
                'priceWithoutDelivery' => $data['priceWithoutDelivery'],
                'deliveryPrice' => $data['deliveryPrice'],
                'driverIncome' => $data['driverIncome'],
            ]
        ];

        $response = Http::post($url, $data);
        return $response;
    }


}
