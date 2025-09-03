<?php
namespace App\Service;

use PDO;
use Exception;

/**
 * RecommendationService class
 * 
 * Implements a hybrid recommendation system combining:
 * 1. Content-based filtering (based on car features)
 * 2. Collaborative filtering (based on user behavior)
 */
class RecommendationService
{
    private PDO $db;
    private int $defaultLimit = 5;
    private float $minSimilarityScore = 0.1; // Minimum similarity score to consider
    private int $maxNeighbors = 5; // Maximum number of similar users to consider

    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get similar cars based on features (Content-based filtering)
     * Uses Jaccard similarity coefficient to find cars with similar features
     * 
     * @param int $carId ID of the base car
     * @param int|null $limit Number of similar cars to return
     * @return array List of similar cars
     */
    public function getSimilarCars(int $carId, ?int $limit = null): array
    {
        try {
            $limit = $limit ?? $this->defaultLimit;

            // Get the base car's features
            $stmt = $this->db->prepare("SELECT features FROM cars WHERE id = ?");
            $stmt->execute([$carId]);
            $baseCarFeatures = $stmt->fetchColumn();
            $baseCarFeatures = $baseCarFeatures ? explode(',', $baseCarFeatures) : [];

            if (empty($baseCarFeatures)) {
                return [];
            }

            // Get cars with similar features
            $query = "SELECT c.* FROM cars c
                      WHERE c.id != :car_id
                      AND c.status = 'available'
                      ORDER BY RAND()
                      LIMIT 10";

            $stmt = $this->db->prepare($query);
            $stmt->execute(['car_id' => $carId]);
            $otherCars = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate similarity scores
            $scores = [];
            foreach ($otherCars as $car) {
                $otherFeatures = $car['features'] ? explode(',', $car['features']) : [];
                
                // Calculate intersection and union
                $intersection = count(array_intersect($baseCarFeatures, $otherFeatures));
                $union = count(array_unique(array_merge($baseCarFeatures, $otherFeatures)));
                
                // Calculate Jaccard similarity
                $similarity = $union > 0 ? $intersection / $union : 0;
                
                if ($similarity >= $this->minSimilarityScore) {
                    $scores[$car['id']] = [
                        'score' => $similarity,
                        'car' => $car
                    ];
                }
            }

            // Sort by similarity score
            uasort($scores, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            $topCars = array_slice($scores, 0, $limit, true);
            
            // Return the cars
            return array_map(function($item) {
                return $item['car'];
            }, $topCars);

        } catch (Exception $e) {
            error_log("Error in getSimilarCars: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recommendations based on user behavior (Collaborative filtering)
     * Uses user booking history and similar users' preferences
     * 
     * @param int $userId ID of the user
     * @param int|null $limit Number of recommendations to return
     * @return array List of recommended cars
     */
    public function getCollaborativeRecommendations(int $userId, ?int $limit = null): array
    {
        try {
            $limit = $limit ?? $this->defaultLimit;

            // 1. Get user's booking history
            $stmt = $this->db->prepare(
                "SELECT DISTINCT car_id 
                 FROM bookings 
                 WHERE user_id = ? AND status != 'cancelled'"
            );
            $stmt->execute([$userId]);
            $userCars = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // If user has no history, return empty array
            if (empty($userCars)) {
                return [];
            }

            // 2. Find similar users based on booking patterns
            $stmt = $this->db->prepare(
                "SELECT user_id, GROUP_CONCAT(DISTINCT car_id) AS car_list
                 FROM bookings
                 WHERE user_id != ? AND status != 'cancelled'
                 GROUP BY user_id"
            );
            $stmt->execute([$userId]);
            $otherUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Calculate user similarity scores
            $userSimilarities = [];
            $userSet = array_flip($userCars);

            foreach ($otherUsers as $user) {
                $otherCars = explode(',', $user['car_list']);
                
                // Calculate Jaccard similarity between users
                $intersection = count(array_intersect($userCars, $otherCars));
                $union = count(array_unique(array_merge($userCars, $otherCars)));
                $similarity = $union > 0 ? $intersection / $union : 0;

                if ($similarity >= $this->minSimilarityScore) {
                    $userSimilarities[$user['user_id']] = $similarity;
                }
            }

            // 4. Get top similar users
            arsort($userSimilarities);
            $similarUsers = array_slice($userSimilarities, 0, $this->maxNeighbors, true);

            if (empty($similarUsers)) {
                return [];
            }

            // 5. Get cars booked by similar users
            $carScores = [];
            foreach ($similarUsers as $otherUserId => $similarity) {
                $stmt = $this->db->prepare(
                    "SELECT DISTINCT car_id 
                     FROM bookings 
                     WHERE user_id = ? AND status != 'cancelled'"
                );
                $stmt->execute([$otherUserId]);
                $otherCars = $stmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($otherCars as $carId) {
                    if (!isset($userSet[$carId])) {
                        $carScores[$carId] = ($carScores[$carId] ?? 0) + $similarity;
                    }
                }
            }

            if (empty($carScores)) {
                return [];
            }

            // 6. Sort and get top recommended cars
            arsort($carScores);
            $topCarIds = array_slice(array_keys($carScores), 0, $limit, true);

            // 7. Get full car details
            $placeholders = implode(',', array_fill(0, count($topCarIds), '?'));
            $stmt = $this->db->prepare(
                "SELECT * FROM cars 
                 WHERE id IN ($placeholders) 
                 AND status = 'available'"
            );
            $stmt->execute($topCarIds);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Error in getCollaborativeRecommendations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get hybrid recommendations combining both approaches
     * 
     * @param int|null $userId ID of the user (null for non-logged in users)
     * @param int $carId ID of the current car
     * @param int|null $limit Number of recommendations to return
     * @return array List of recommended cars
     */
    public function getHybridRecommendations(?int $userId, int $carId, ?int $limit = null): array
    {
        try {
            $limit = $limit ?? $this->defaultLimit;
            
            // Get recommendations from both approaches
            $contentBased = $this->getSimilarCars($carId, $limit * 2);
            $collaborative = $userId ? $this->getCollaborativeRecommendations($userId, $limit * 2) : [];

            // Merge and deduplicate results
            $merged = [];
            foreach (array_merge($contentBased, $collaborative) as $car) {
                $merged[$car['id']] = $car;
            }

            // Return balanced set of recommendations
            return array_slice(array_values($merged), 0, $limit);

        } catch (Exception $e) {
            error_log("Error in getHybridRecommendations: " . $e->getMessage());
            return [];
        }
    }

    public function getContentBasedRecommendations(int $carId, ?int $limit = null): array
    {
        // Get target car's features
        $stmt = $this->db->prepare("SELECT features FROM cars WHERE id = ?");
        $stmt->execute([$carId]);
        $targetFeatures = $stmt->fetchColumn();

        if (empty($targetFeatures)) {
            return [];
        }

        // Get all other available cars with their features
        $stmt = $this->db->prepare("
            SELECT c.*, GROUP_CONCAT(cf.feature) as features
            FROM cars c
            JOIN car_features cf ON c.id = cf.car_id
            WHERE c.id != ? AND c.status = 'available'
            GROUP BY c.id
        ");
        $stmt->execute([$carId]);
        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate similarity scores
        $recommendations = [];
        foreach ($cars as $car) {
            $carFeatures = explode(',', $car['features']);
            $similarity = $this->calculateJaccardSimilarity($targetFeatures, $carFeatures);
            $recommendations[] = [
                'car' => $car,
                'similarity' => $similarity
            ];
        }

        // Sort by similarity score
        usort($recommendations, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        // Get top N recommendations
        if ($limit !== null) {
            $recommendations = array_slice($recommendations, 0, $limit);
        }

        // Get full car details for recommended cars
        $recommendedCars = [];
        foreach ($recommendations as $rec) {
            $carId = $rec['car']['id'];
            $stmt = $this->db->prepare("SELECT * FROM cars WHERE id = ?");
            $stmt->execute([$carId]);
            $car = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($car) {
                $car['similarity_score'] = $rec['similarity'];
                $recommendedCars[] = $car;
            }
        }

        return $recommendedCars;
    }

    private function calculateJaccardSimilarity(array $set1, array $set2): float {
        if (empty($set1) || empty($set2)) {
            return 0.0;
        }

        $intersection = array_intersect($set1, $set2);
        $union = array_unique(array_merge($set1, $set2));

        return count($intersection) / count($union);
    }
}
