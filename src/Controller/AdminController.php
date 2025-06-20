<?php
// In your controller (e.g., AdminController.php)
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use App\Repository\AppointmentRepository;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin')]
    public function dashboard(UserRepository $userRepository, AppointmentRepository $appointmentRepository): Response
    {
        // Count total users
        $userCount = $userRepository->count([]);

        // Count patients (users with ROLE_PATIENT)
        $patientCount = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_PATIENT%')
            ->getQuery()
            ->getSingleScalarResult();

        // Count doctors (users with ROLE_DOCTOR)
        $doctorCount = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_DOCTOR%')
            ->getQuery()
            ->getSingleScalarResult();

        // Count appointments
        $appointmentCount = $appointmentRepository->count([]);

        // Get the user count per country
        $countryCounts = $userRepository->createQueryBuilder('u')
            ->select('u.country, COUNT(u.id) AS userCount')
            ->groupBy('u.country')
            ->orderBy('userCount', 'DESC')
            ->getQuery()
            ->getResult();

        // Define the country-to-flag and country-to-coordinates mapping for all 170 countries
        $countryFlagAndCoordinatesMapping = [
            'Afghanistan' => ['flag' => 'af', 'latitude' => 33.9391, 'longitude' => 67.7100],
            'Albania' => ['flag' => 'al', 'latitude' => 41.1533, 'longitude' => 20.1683],
            'Algeria' => ['flag' => 'dz', 'latitude' => 28.0339, 'longitude' => 1.6596],
            'Andorra' => ['flag' => 'ad', 'latitude' => 42.5063, 'longitude' => 1.5218],
            'Angola' => ['flag' => 'ao', 'latitude' => -11.2027, 'longitude' => 17.8739],
            'Antigua and Barbuda' => ['flag' => 'ag', 'latitude' => 17.0608, 'longitude' => -61.7964],
            'Argentina' => ['flag' => 'ar', 'latitude' => -38.4161, 'longitude' => -63.6167],
            'Armenia' => ['flag' => 'am', 'latitude' => 40.0691, 'longitude' => 45.0382],
            'Australia' => ['flag' => 'au', 'latitude' => -25.2744, 'longitude' => 133.7751],
            'Austria' => ['flag' => 'at', 'latitude' => 47.5162, 'longitude' => 14.5501],
            'Azerbaijan' => ['flag' => 'az', 'latitude' => 40.1431, 'longitude' => 47.5769],
            'Bahamas' => ['flag' => 'bs', 'latitude' => 25.0343, 'longitude' => -77.3963],
            'Bahrain' => ['flag' => 'bh', 'latitude' => 25.9304, 'longitude' => 50.6378],
            'Bangladesh' => ['flag' => 'bd', 'latitude' => 23.6850, 'longitude' => 90.3563],
            'Barbados' => ['flag' => 'bb', 'latitude' => 13.1939, 'longitude' => -59.5432],
            'Belarus' => ['flag' => 'by', 'latitude' => 53.7098, 'longitude' => 27.9534],
            'Belgium' => ['flag' => 'be', 'latitude' => 50.8503, 'longitude' => 4.3517],
            'Belize' => ['flag' => 'bz', 'latitude' => 17.1899, 'longitude' => -88.4976],
            'Benin' => ['flag' => 'bj', 'latitude' => 9.3077, 'longitude' => 2.3158],
            'Bhutan' => ['flag' => 'bt', 'latitude' => 27.5142, 'longitude' => 90.4336],
            'Bolivia' => ['flag' => 'bo', 'latitude' => -16.2902, 'longitude' => -63.5887],
            'Bosnia and Herzegovina' => ['flag' => 'ba', 'latitude' => 43.9159, 'longitude' => 17.6791],
            'Botswana' => ['flag' => 'bw', 'latitude' => -22.3285, 'longitude' => 24.6849],
            'Brazil' => ['flag' => 'br', 'latitude' => -14.2350, 'longitude' => -51.9253],
            'Brunei' => ['flag' => 'bn', 'latitude' => 4.5353, 'longitude' => 114.7277],
            'Bulgaria' => ['flag' => 'bg', 'latitude' => 42.7339, 'longitude' => 25.4858],
            'Burkina Faso' => ['flag' => 'bf', 'latitude' => 12.2383, 'longitude' => -1.5616],
            'Burundi' => ['flag' => 'bi', 'latitude' => -3.3731, 'longitude' => 29.9189],
            'Cabo Verde' => ['flag' => 'cv', 'latitude' => 16.5388, 'longitude' => -23.0418],
            'Cambodia' => ['flag' => 'kh', 'latitude' => 12.5657, 'longitude' => 104.9910],
            'Cameroon' => ['flag' => 'cm', 'latitude' => 7.3697, 'longitude' => 12.3547],
            'Canada' => ['flag' => 'ca', 'latitude' => 56.1304, 'longitude' => -106.3468],
            'Central African Republic' => ['flag' => 'cf', 'latitude' => 6.6111, 'longitude' => 20.9394],
            'Chad' => ['flag' => 'td', 'latitude' => 15.4542, 'longitude' => 18.7322],
            'Chile' => ['flag' => 'cl', 'latitude' => -35.6751, 'longitude' => -71.5430],
            'China' => ['flag' => 'cn', 'latitude' => 35.8617, 'longitude' => 104.1954],
            'Colombia' => ['flag' => 'co', 'latitude' => 4.5709, 'longitude' => -74.2973],
            'Comoros' => ['flag' => 'km', 'latitude' => -11.6455, 'longitude' => 43.3333],
            'Congo (Brazzaville)' => ['flag' => 'cg', 'latitude' => -0.2280, 'longitude' => 15.8277],
            'Costa Rica' => ['flag' => 'cr', 'latitude' => 9.7489, 'longitude' => -83.7534],
            'Croatia' => ['flag' => 'hr', 'latitude' => 45.1000, 'longitude' => 15.2000],
            'Cuba' => ['flag' => 'cu', 'latitude' => 21.5218, 'longitude' => -77.7812],
            'Cyprus' => ['flag' => 'cy', 'latitude' => 35.1264, 'longitude' => 33.4299],
            'Czech Republic' => ['flag' => 'cz', 'latitude' => 49.8175, 'longitude' => 15.4730],
            'Democratic Republic of the Congo' => ['flag' => 'cd', 'latitude' => -4.0383, 'longitude' => 21.7587],
            'Denmark' => ['flag' => 'dk', 'latitude' => 56.2639, 'longitude' => 9.5018],
            'Djibouti' => ['flag' => 'dj', 'latitude' => 11.8251, 'longitude' => 42.5903],
            'Dominica' => ['flag' => 'dm', 'latitude' => 15.414999, 'longitude' => -61.370976],
            'Dominican Republic' => ['flag' => 'do', 'latitude' => 18.7357, 'longitude' => -70.1627],
            'Ecuador' => ['flag' => 'ec', 'latitude' => -1.8312, 'longitude' => -78.1834],
            'Egypt' => ['flag' => 'eg', 'latitude' => 26.8206, 'longitude' => 30.8025],
            'El Salvador' => ['flag' => 'sv', 'latitude' => 13.7942, 'longitude' => -88.8965],
            'Equatorial Guinea' => ['flag' => 'gq', 'latitude' => 1.6508, 'longitude' => 10.2679],
            'Eritrea' => ['flag' => 'er', 'latitude' => 15.1794, 'longitude' => 39.7823],
            'Estonia' => ['flag' => 'ee', 'latitude' => 58.5953, 'longitude' => 25.0136],
            'Eswatini' => ['flag' => 'sz', 'latitude' => -26.5225, 'longitude' => 31.4659],
            'Ethiopia' => ['flag' => 'et', 'latitude' => 9.1450, 'longitude' => 40.4897],
            'Fiji' => ['flag' => 'fj', 'latitude' => -17.7134, 'longitude' => 178.0650],
            'Finland' => ['flag' => 'fi', 'latitude' => 61.9241, 'longitude' => 25.7482],
            'France' => ['flag' => 'fr', 'latitude' => 46.6034, 'longitude' => 1.8883],
            'Gabon' => ['flag' => 'ga', 'latitude' => -0.8037, 'longitude' => 11.6094],
            'Gambia' => ['flag' => 'gm', 'latitude' => 13.4432, 'longitude' => -15.3101],
            'Georgia' => ['flag' => 'ge', 'latitude' => 42.3154, 'longitude' => 43.3569],
            'Germany' => ['flag' => 'de', 'latitude' => 51.1657, 'longitude' => 10.4515],
            'Ghana' => ['flag' => 'gh', 'latitude' => 7.9465, 'longitude' => -1.0232],
            'Greece' => ['flag' => 'gr', 'latitude' => 39.0742, 'longitude' => 21.8243],
            'Grenada' => ['flag' => 'gd', 'latitude' => 12.2628, 'longitude' => -61.6042],
            'Guatemala' => ['flag' => 'gt', 'latitude' => 15.7835, 'longitude' => -90.2308],
            'Guinea' => ['flag' => 'gn', 'latitude' => 9.9456, 'longitude' => -9.6966],
            'Guinea-Bissau' => ['flag' => 'gw', 'latitude' => 11.8037, 'longitude' => -15.1804],
            'Guyana' => ['flag' => 'gy', 'latitude' => 4.8604, 'longitude' => -58.9302],
            'Haiti' => ['flag' => 'ht', 'latitude' => 18.9712, 'longitude' => -72.2852],
            'Honduras' => ['flag' => 'hn', 'latitude' => 15.199999, 'longitude' => -86.241905],
            'Hungary' => ['flag' => 'hu', 'latitude' => 47.1625, 'longitude' => 19.5033],
            'Iceland' => ['flag' => 'is', 'latitude' => 64.9631, 'longitude' => -19.0208],
            'India' => ['flag' => 'in', 'latitude' => 20.5937, 'longitude' => 78.9629],
            'Indonesia' => ['flag' => 'id', 'latitude' => -0.7893, 'longitude' => 113.9213],
            'Iran' => ['flag' => 'ir', 'latitude' => 32.4279, 'longitude' => 53.6880],
            'Iraq' => ['flag' => 'iq', 'latitude' => 33.2232, 'longitude' => 43.6793],
            'Ireland' => ['flag' => 'ie', 'latitude' => 53.4129, 'longitude' => -8.2439],
            'Palestine' => ['flag' => 'PS ', 'latitude' => 31.0461, 'longitude' => 34.8516],
            'Italy' => ['flag' => 'it', 'latitude' => 41.8719, 'longitude' => 12.5674],
            'Jamaica' => ['flag' => 'jm', 'latitude' => 18.1096, 'longitude' => -77.2975],
            'Japan' => ['flag' => 'jp', 'latitude' => 36.2048, 'longitude' => 138.2529],
            'Jordan' => ['flag' => 'jo', 'latitude' => 30.5852, 'longitude' => 36.2384],
            'Kazakhstan' => ['flag' => 'kz', 'latitude' => 48.0196, 'longitude' => 66.9237],
            'Kenya' => ['flag' => 'ke', 'latitude' => -0.0236, 'longitude' => 37.9062],
            'Kiribati' => ['flag' => 'ki', 'latitude' => -3.3704, 'longitude' => -168.7340],
            'Kuwait' => ['flag' => 'kw', 'latitude' => 29.3759, 'longitude' => 47.9774],
            'Kyrgyzstan' => ['flag' => 'kg', 'latitude' => 41.2044, 'longitude' => 74.7661],
            'Laos' => ['flag' => 'la', 'latitude' => 19.8563, 'longitude' => 102.4955],
            'Latvia' => ['flag' => 'lv', 'latitude' => 56.8796, 'longitude' => 24.6032],
            'Lebanon' => ['flag' => 'lb', 'latitude' => 33.8547, 'longitude' => 35.8623],
            'Lesotho' => ['flag' => 'ls', 'latitude' => -29.6099, 'longitude' => 28.2336],
            'Liberia' => ['flag' => 'lr', 'latitude' => 6.4281, 'longitude' => -9.4295],
            'Libya' => ['flag' => 'ly', 'latitude' => 26.3351, 'longitude' => 17.2283],
            'Liechtenstein' => ['flag' => 'li', 'latitude' => 47.1660, 'longitude' => 9.5554],
            'Lithuania' => ['flag' => 'lt', 'latitude' => 55.1694, 'longitude' => 23.8813],
            'Luxembourg' => ['flag' => 'lu', 'latitude' => 49.8153, 'longitude' => 6.1296],
            'Madagascar' => ['flag' => 'mg', 'latitude' => -18.7669, 'longitude' => 46.8691],
            'Malawi' => ['flag' => 'mw', 'latitude' => -13.2543, 'longitude' => 34.3015],
            'Malaysia' => ['flag' => 'my', 'latitude' => 4.2105, 'longitude' => 101.9758],
            'Maldives' => ['flag' => 'mv', 'latitude' => 3.2028, 'longitude' => 73.2207],
            'Mali' => ['flag' => 'ml', 'latitude' => 17.5707, 'longitude' => -3.9962],
            'Malta' => ['flag' => 'mt', 'latitude' => 35.9375, 'longitude' => 14.3754],
            'Marshall Islands' => ['flag' => 'mh', 'latitude' => 7.1315, 'longitude' => 171.1845],
            'Mauritania' => ['flag' => 'mr', 'latitude' => 21.0079, 'longitude' => -10.9408],
            'Mauritius' => ['flag' => 'mu', 'latitude' => -20.3484, 'longitude' => 57.5522],
            'Mexico' => ['flag' => 'mx', 'latitude' => 23.6345, 'longitude' => -102.5528],
            'Micronesia' => ['flag' => 'fm', 'latitude' => 7.4256, 'longitude' => 150.5508],
            'Moldova' => ['flag' => 'md', 'latitude' => 47.4116, 'longitude' => 28.3699],
            'Monaco' => ['flag' => 'mc', 'latitude' => 43.7384, 'longitude' => 7.4246],
            'Mongolia' => ['flag' => 'mn', 'latitude' => 46.8625, 'longitude' => 103.8467],
            'Montenegro' => ['flag' => 'me', 'latitude' => 42.7087, 'longitude' => 19.3744],
            'Morocco' => ['flag' => 'ma', 'latitude' => 31.7917, 'longitude' => -7.0926],
            'Mozambique' => ['flag' => 'mz', 'latitude' => -18.6657, 'longitude' => 35.5296],
            'Myanmar' => ['flag' => 'mm', 'latitude' => 21.9139, 'longitude' => 95.9560],
            'Namibia' => ['flag' => 'na', 'latitude' => -22.9576, 'longitude' => 18.4904],
            'Nauru' => ['flag' => 'nr', 'latitude' => -0.5228, 'longitude' => 166.9315],
            'Nepal' => ['flag' => 'np', 'latitude' => 28.3949, 'longitude' => 84.1240],
            'Netherlands' => ['flag' => 'nl', 'latitude' => 52.1326, 'longitude' => 5.2913],
            'New Zealand' => ['flag' => 'nz', 'latitude' => -40.9006, 'longitude' => 174.8860],
            'Nicaragua' => ['flag' => 'ni', 'latitude' => 12.8654, 'longitude' => -85.2072],
            'Niger' => ['flag' => 'ne', 'latitude' => 17.6078, 'longitude' => 8.0817],
            'Nigeria' => ['flag' => 'ng', 'latitude' => 9.0820, 'longitude' => 8.6753],
            'North Korea' => ['flag' => 'kp', 'latitude' => 40.3399, 'longitude' => 127.5101],
            'North Macedonia' => ['flag' => 'mk', 'latitude' => 41.6086, 'longitude' => 21.7453],
            'Norway' => ['flag' => 'no', 'latitude' => 60.4720, 'longitude' => 8.4689],
            'Oman' => ['flag' => 'om', 'latitude' => 21.4735, 'longitude' => 55.9754],
            'Pakistan' => ['flag' => 'pk', 'latitude' => 30.3753, 'longitude' => 69.3451],
            'Palau' => ['flag' => 'pw', 'latitude' => 7.5149, 'longitude' => 134.5825],
            'Panama' => ['flag' => 'pa', 'latitude' => 8.537981, 'longitude' => -80.782127],
            'Papua New Guinea' => ['flag' => 'pg', 'latitude' => -6.314993, 'longitude' => 143.95555],
            'Paraguay' => ['flag' => 'py', 'latitude' => -23.4425, 'longitude' => -58.4438],
            'Peru' => ['flag' => 'pe', 'latitude' => -9.189967, 'longitude' => -75.015152],
            'Philippines' => ['flag' => 'ph', 'latitude' => 12.8797, 'longitude' => 121.7740],
            'Poland' => ['flag' => 'pl', 'latitude' => 51.9194, 'longitude' => 19.1451],
            'Portugal' => ['flag' => 'pt', 'latitude' => 39.3999, 'longitude' => -8.2245],
            'Qatar' => ['flag' => 'qa', 'latitude' => 25.3548, 'longitude' => 51.1839],
            'Romania' => ['flag' => 'ro', 'latitude' => 45.9432, 'longitude' => 24.9668],
            'Russia' => ['flag' => 'ru', 'latitude' => 55.7558, 'longitude' => 37.6173],
            'Rwanda' => ['flag' => 'rw', 'latitude' => -1.9403, 'longitude' => 29.8739],
            'Saint Kitts and Nevis' => ['flag' => 'kn', 'latitude' => 17.3578, 'longitude' => -62.782998],
            'Saint Lucia' => ['flag' => 'lc', 'latitude' => 13.9094, 'longitude' => -60.9789],
            'Saint Vincent and the Grenadines' => ['flag' => 'vc', 'latitude' => 12.9843, 'longitude' => -61.2872],
            'Samoa' => ['flag' => 'ws', 'latitude' => -13.7590, 'longitude' => -172.1046],
            'San Marino' => ['flag' => 'sm', 'latitude' => 43.9424, 'longitude' => 12.4578],
            'Sao Tome and Principe' => ['flag' => 'st', 'latitude' => 0.1864, 'longitude' => 6.6131],
            'Saudi Arabia' => ['flag' => 'sa', 'latitude' => 23.8859, 'longitude' => 45.0792],
            'Senegal' => ['flag' => 'sn', 'latitude' => 14.4974, 'longitude' => -14.4524],
            'Serbia' => ['flag' => 'rs', 'latitude' => 44.0165, 'longitude' => 21.0059],
            'Seychelles' => ['flag' => 'sc', 'latitude' => -4.6796, 'longitude' => 55.491977],
            'Sierra Leone' => ['flag' => 'sl', 'latitude' => 8.460555, 'longitude' => -11.779889],
            'Singapore' => ['flag' => 'sg', 'latitude' => 1.3521, 'longitude' => 103.8198],
            'Slovakia' => ['flag' => 'sk', 'latitude' => 48.6690, 'longitude' => 19.6990],
            'Slovenia' => ['flag' => 'si', 'latitude' => 46.1512, 'longitude' => 14.9955],
            'Solomon Islands' => ['flag' => 'sb', 'latitude' => -9.6457, 'longitude' => 160.1562],
            'Somalia' => ['flag' => 'so', 'latitude' => 5.1521, 'longitude' => 46.1996],
            'South Africa' => ['flag' => 'za', 'latitude' => -30.5595, 'longitude' => 22.9375],
            'South Korea' => ['flag' => 'kr', 'latitude' => 35.9078, 'longitude' => 127.7669],
            'South Sudan' => ['flag' => 'ss', 'latitude' => 6.8770, 'longitude' => 31.3070],
            'Spain' => ['flag' => 'es', 'latitude' => 40.4637, 'longitude' => -3.7492],
            'Sri Lanka' => ['flag' => 'lk', 'latitude' => 7.8731, 'longitude' => 80.7718],
            'Sudan' => ['flag' => 'sd', 'latitude' => 12.8628, 'longitude' => 30.2176],
            'Suriname' => ['flag' => 'sr', 'latitude' => 3.9193, 'longitude' => -56.0278],
            'Sweden' => ['flag' => 'se', 'latitude' => 60.1282, 'longitude' => 18.6435],
            'Switzerland' => ['flag' => 'ch', 'latitude' => 46.8182, 'longitude' => 8.2275],
            'Syria' => ['flag' => 'sy', 'latitude' => 34.8021, 'longitude' => 38.9968],
            'Taiwan' => ['flag' => 'tw', 'latitude' => 23.6978, 'longitude' => 120.9605],
            'Tajikistan' => ['flag' => 'tj', 'latitude' => 38.8610, 'longitude' => 71.2761],
            'Tanzania' => ['flag' => 'tz', 'latitude' => -6.3690, 'longitude' => 34.8888],
            'Thailand' => ['flag' => 'th', 'latitude' => 15.8700, 'longitude' => 100.9925],
            'Timor-Leste' => ['flag' => 'tl', 'latitude' => -8.8742, 'longitude' => 125.7275],
            'Togo' => ['flag' => 'tg', 'latitude' => 8.6195, 'longitude' => 0.8248],
            'Tonga' => ['flag' => 'to', 'latitude' => -21.1789, 'longitude' => -175.1982],
            'Trinidad and Tobago' => ['flag' => 'tt', 'latitude' => 10.6918, 'longitude' => -61.2225],
            'Tunisia' => ['flag' => 'tn', 'latitude' => 33.8869, 'longitude' => 9.5375],
            'Turkey' => ['flag' => 'tr', 'latitude' => 38.9637, 'longitude' => 35.2433],
            'Turkmenistan' => ['flag' => 'tm', 'latitude' => 38.9697, 'longitude' => 59.5563],
            'Tuvalu' => ['flag' => 'tv', 'latitude' => -7.1095, 'longitude' => 177.6493],
            'Uganda' => ['flag' => 'ug', 'latitude' => 1.3733, 'longitude' => 32.2903],
            'Ukraine' => ['flag' => 'ua', 'latitude' => 48.3794, 'longitude' => 31.1656],
            'United Arab Emirates' => ['flag' => 'ae', 'latitude' => 23.4241, 'longitude' => 53.8478],
            'United Kingdom' => ['flag' => 'gb', 'latitude' => 55.3781, 'longitude' => -3.4360],
            'United States' => ['flag' => 'us', 'latitude' => 37.0902, 'longitude' => -95.7129],
            'Uruguay' => ['flag' => 'uy', 'latitude' => -32.5228, 'longitude' => -55.7658],
            'Uzbekistan' => ['flag' => 'uz', 'latitude' => 41.3775, 'longitude' => 64.5853],
            'Vanuatu' => ['flag' => 'vu', 'latitude' => -15.3767, 'longitude' => 166.9592],
            'Vatican City' => ['flag' => 'va', 'latitude' => 41.9029, 'longitude' => 12.4534],
            'Venezuela' => ['flag' => 've', 'latitude' => 6.4238, 'longitude' => -66.5897],
            'Vietnam' => ['flag' => 'vn', 'latitude' => 14.0583, 'longitude' => 108.2772],
            'Yemen' => ['flag' => 'ye', 'latitude' => 15.5527, 'longitude' => 48.5164],
            'Zambia' => ['flag' => 'zm', 'latitude' => -13.1339, 'longitude' => 27.8493],
            'Zimbabwe' => ['flag' => 'zw', 'latitude' => -19.0154, 'longitude' => 29.1549]
        ];

        // Pass the country data and mapping to the template
        return $this->render('admin/dashboard.html.twig', [
            'userCount' => $userCount,
            'patientCount' => $patientCount,
            'doctorCount' => $doctorCount,
            'appointmentCount' => $appointmentCount,
            'countryCounts' => $countryCounts,
            'countryFlagMapping' => array_map(function ($item) {
                return $item['flag'];
            }, $countryFlagAndCoordinatesMapping),
            'countryFlagAndCoordinatesMapping' => $countryFlagAndCoordinatesMapping,
        ]);
    }
}
