<?php


namespace App\Service;

use App\Repository\CallRepository;
use App\Repository\CityRepository;
use App\Repository\ConcessionRepository;
use App\Repository\ServiceHeadRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use Cocur\Slugify\Slugify;

/**
 * Class HeadBoardData
 * @package App\Service
 */
class HeadBoardData
{
    private $callRepository;
    private $cityRepository;
    private $concessionRepository;
    private $serviceRepository;
    private $userRepository;
    private $serviceHeadRepository;

    public function __construct(
        CallRepository $callRepository,
        CityRepository $cityRepository,
        ConcessionRepository $concessionRepository,
        ServiceRepository $serviceRepository,
        UserRepository $userRepository,
        ServiceHeadRepository $serviceHeadRepository
    ) {
        $this->callRepository        = $callRepository;
        $this->cityRepository        = $cityRepository;
        $this->concessionRepository  = $concessionRepository;
        $this->serviceRepository     = $serviceRepository;
        $this->userRepository        = $userRepository;
        $this->serviceHeadRepository = $serviceHeadRepository;
    }

    /**
     * @param array $data
     * $data is provided by ServiceHeadRepository->getHeadServiceCalls()
     * The array generated by this method is like below
     * $array = [
     *      cityName => [
     *              'slug'       => city-name-slug,
     *              'in_process' => int,
     *              'to_process' => int,
     *              'concessions' => [
     *                  'concessionName' => [
     *                      'slug' => concession-name-slug,
     *                      'in_process' => int,
     *                      'to_process' => int,
     *                      'services'   => [
     *                          'serviceName' => [
     *                              'slug' => service-name-slug,
     *                              'in_process'    => int,
     *                              'to_process'    => int,
     *                              'collaborators' => [
     *                                  'userFirstName userName' => [
     *                                      'user_name'  => 'userFirstName userName',
     *                                      'to_process' => int,
     *                                      'in_process' => int,
     *                                      'user_id'    => userId
     *                                  ],
     *                              ],
     *                          ],
     *                      ],
     *                  ],
     *               ],
     *          ]
     *
     * @return array
     */

    public function makeDataForHead(array $data): array
    {
        $result = [];
        $slugify = new Slugify();
        foreach ($data as $datum) {
            $serviceId     = (int)$datum['service_id'];
            $service       = $this->serviceRepository->findOneById($serviceId);
            $serviceName   = $service->getName();
            $collaborators = $service->getUsers();

            $result[$datum['city']]['slug'] = $slugify->slugify($datum['city']);
            $result[$datum['city']]['in_process'] = (!isset($result[$datum['city']]['in_process']))
                ? 0 : $result[$datum['city']]['in_process'];
            $result[$datum['city']]['to_process'] = (!isset($result[$datum['city']]['to_process']))
                ? 0 : $result[$datum['city']]['to_process'];

            $result[$datum['city']]['concessions'][$datum['concession']]['slug']
                = $slugify->slugify($datum['concession']);

            $result[$datum['city']]['concessions'][$datum['concession']]['in_process'] = (!isset($result[$datum['city']]
                ['concessions'][$datum['concession']]['in_process'])) ? 0 : $result[$datum['city']]['concessions']
                [$datum['concession']]['in_process'];

            $result[$datum['city']]['concessions'][$datum['concession']]['to_process'] = (!isset($result[$datum['city']]
                ['concessions'][$datum['concession']]['to_process'])) ? 0 : $result[$datum['city']]['concessions']
                [$datum['concession']]['to_process'];


            $result[$datum['city']]['concessions'][$datum['concession']]['services'][$serviceName] = [
                'to_process'    => 0,
                'in_process'    => 0,
                'collaborators' => [],
                'slug'          => $slugify->slugify($serviceName),
            ];
            foreach ($collaborators as $collaborator) {
                $collaboratorName = $collaborator->getFirstname() . ' ' . $collaborator->getLastname();
                $result[$datum['city']]['concessions'][$datum['concession']]['services'][$serviceName]['collaborators']
                    [$collaboratorName] =
                    [
                        'user_id'    => $collaborator->getId(),
                        'slug'       => $slugify->slugify($collaboratorName),
                        'user_name'  => $collaboratorName,
                        'to_process' => count($this->callRepository->callsToProcessByUser($collaborator)),
                        'in_process' => count($this->callRepository->callsInProcessByUser($collaborator)),
                    ];
                $result[$datum['city']]['to_process'] += $result[$datum['city']]['concessions'][$datum['concession']]
                ['services'][$serviceName]['collaborators'][$collaboratorName]['to_process'];
                $result[$datum['city']]['in_process'] += $result[$datum['city']]['concessions'][$datum['concession']]
                ['services'][$serviceName]['collaborators'][$collaboratorName]['in_process'];
                $result[$datum['city']]['concessions'][$datum['concession']]['to_process'] += $result[$datum['city']]
                ['concessions'][$datum['concession']]['services'][$serviceName]['collaborators']
                [$collaboratorName]['to_process'];
                $result[$datum['city']]['concessions'][$datum['concession']]['in_process'] += $result[$datum['city']]
                ['concessions'][$datum['concession']]['services'][$serviceName]['collaborators']
                [$collaboratorName]['in_process'];
                $result[$datum['city']]['concessions'][$datum['concession']]['services'][$serviceName]['to_process'] +=
                    $result[$datum['city']]['concessions'][$datum['concession']]['services'][$serviceName]
                    ['collaborators'][$collaboratorName]['to_process'];
                $result[$datum['city']]['concessions'][$datum['concession']]['services'][$serviceName]['in_process'] +=
                    $result[$datum['city']]['concessions'][$datum['concession']]['services'][$serviceName]
                    ['collaborators'][$collaboratorName]['in_process'];
            }
        }
        return $result;
    }


    public function makeDataForHeadUpdater(array $data): array
    {
        $result = [];
        $slugify = new Slugify();
        foreach ($data as $datum) {
            $serviceId     = (int)$datum['service_id'];
            $service       = $this->serviceRepository->findOneById($serviceId);
            $serviceName   = $service->getName();
            $collaborators = $service->getUsers();

            $citySlug = $slugify->slugify($datum['city']);

            $result[$citySlug . '-in-process'] = (!isset($result[$citySlug. '-in-process']))
                ? 0 : $result[$citySlug . '-in-process'];
            $result[$citySlug . '-to-process'] = (!isset($result[$citySlug. '-to-process']))
                ? 0 : $result[$citySlug . '-to-process'];

            $concessionSlug    = $slugify->slugify($datum['concession']);

            $result[$citySlug . '-' . $concessionSlug . '-in-process'] =
                (!isset($result[$citySlug . '-' . $concessionSlug . '-in-process'])) ?
                    0 : $result[$citySlug . '-' . $concessionSlug . '-in-process'];

            $result[$citySlug . '-' . $concessionSlug . '-to-process'] =
                (!isset($result[$citySlug . '-' . $concessionSlug . '-to-process'])) ?
                    0 : $result[$citySlug . '-' . $concessionSlug . '-to-process'];

            $serviceSlug = $slugify->slugify($serviceName);

            $result[$citySlug . '-' . $concessionSlug . '-' . $serviceSlug .'-to-process'] =
                (!isset($result[$citySlug . '-' . $concessionSlug . '-' . $serviceSlug .'-to-process'])) ?
                    0 : $result[$citySlug . '-' . $concessionSlug . '-' . $serviceSlug .'-to-process'];
            $result[$citySlug . '-' . $concessionSlug . '-' . $serviceSlug .'-in-process'] =
                (!isset($result[$citySlug . '-' . $concessionSlug . '-' . $serviceSlug .'-in-process'])) ?
                    0 : $result[$citySlug . '-' . $concessionSlug . '-' . $serviceSlug .'-in-process'];

            foreach ($collaborators as $collaborator) {
                $collSlug =
                    $slugify->slugify($collaborator->getFirstname() . ' ' . $collaborator->getLastname());
                $result[$citySlug . '-' . $concessionSlug . '-' . $serviceSlug . '-' . $collSlug . '-to-process'] =
                    count($this->callRepository->callsToProcessByUser($collaborator));
                $result[$citySlug . '-' . $concessionSlug . '-' . $serviceSlug . '-' . $collSlug . '-in-process'] =
                    count($this->callRepository->callsInProcessByUser($collaborator));

                $result[$citySlug . '-to-process'] +=
                    $result[$citySlug . '-' . $concessionSlug . '-' . $serviceSlug . '-' . $collSlug . '-to-process'];
                $result[$citySlug . '-in-process'] +=
                    $result[$citySlug . '-' . $concessionSlug . '-' . $serviceSlug . '-' . $collSlug . '-in-process'];
                $result[$citySlug . '-' . $concessionSlug . '-to-process'] +=
                    $result[$citySlug . '-' . $concessionSlug . '-' . $serviceSlug . '-' . $collSlug . '-to-process'];
                $result[$citySlug . '-' . $concessionSlug . '-in-process'] +=
                    $result[$citySlug . '-' . $concessionSlug . '-' . $serviceSlug . '-' . $collSlug . '-in-process'];
                $result[$citySlug . '-' . $concessionSlug . '-' . $serviceSlug .  '-to-process'] +=
                    $result[$citySlug . '-' . $concessionSlug . '-' . $serviceSlug . '-' . $collSlug . '-to-process'];
                $result[$citySlug . '-' . $concessionSlug . '-' . $serviceSlug .  '-in-process'] +=
                    $result[$citySlug . '-' . $concessionSlug . '-' . $serviceSlug . '-' . $collSlug . '-in-process'];
            }
        }
        return $result;
    }
}
