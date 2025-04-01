<?php
declare(strict_types=1);

namespace App\Models;

/**
 * AuditSection Model
 * Handles audit sections including crud operations
 */
class AuditSection extends Model
{
    protected string $table = 'audit_sections';
    protected string $primaryKey = 'section_id';

    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute a query and return all results
     * 
     * @param string $sql The SQL query to execute
     * @param array $params The parameters for the query
     * @return array The results of the query
     */
    protected function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get sections for a specific audit
     * 
     * @param int $auditId Audit ID
     * @return array|null Array of sections or null if none found
     */
    public function getSectionsByAuditId(int $auditId): ?array
    {
        $sql = "SELECT s.*, COUNT(q.question_id) as question_count 
                FROM {$this->table} s
                LEFT JOIN audit_questions q ON s.section_id = q.section_id
                WHERE s.audit_id = ?
                GROUP BY s.section_id
                ORDER BY s.section_id";
        
        return $this->fetchAll($sql, [$auditId]);
    }

    /**
     * Get section by ID
     * 
     * @param int $sectionId Section ID
     * @return array|null Section details or null if not found
     */
    public function getSectionById(int $sectionId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->fetchOne($sql, [$sectionId]);
    }

    /**
     * Create a new section
     * 
     * @param array $data Section data
     * @return int|bool New section ID or false on failure
     */
    public function createSection(array $data): int|bool
    {
        // Required fields
        $requiredFields = ['audit_id', 'title'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        // Set default weight if not provided
        if (!isset($data['weight'])) {
            $data['weight'] = 1.00;
        }
        
        // Set default position if not provided
        if (!isset($data['position'])) {
            // Get max position for this audit
            $sql = "SELECT MAX(position) as max_position FROM {$this->table} WHERE audit_id = ?";
            $result = $this->fetchOne($sql, [$data['audit_id']]);
            $data['position'] = $result ? ($result['max_position'] + 1) : 0;
        }
        
        return $this->create($data);
    }

    /**
     * Update an existing section
     * 
     * @param int $sectionId Section ID
     * @param array $data Updated section data
     * @return bool Success or failure
     */
    public function updateSection(int $sectionId, array $data): bool
    {
        return $this->update($sectionId, $data);
    }

    /**
     * Delete a section
     * 
     * @param int $sectionId Section ID
     * @return bool Success or failure
     */
    public function deleteSection(int $sectionId): bool
    {
        return $this->delete($sectionId);
    }

    /**
     * Reorder sections
     * 
     * @param int $auditId Audit ID
     * @param array $sectionOrder Array of section IDs in order
     * @return bool Success or failure
     */
    public function reorderSections(int $auditId, array $sectionOrder): bool
    {
        if (empty($sectionOrder)) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            $sql = "UPDATE {$this->table} SET position = ? WHERE {$this->primaryKey} = ? AND audit_id = ?";
            $stmt = $this->db->prepare($sql);
            
            foreach ($sectionOrder as $position => $sectionId) {
                $stmt->execute([$position, $sectionId, $auditId]);
            }
            
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Error reordering sections: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get full section with questions
     * 
     * @param int $sectionId Section ID
     * @return array|null Section with questions or null if not found
     */
    public function getSectionWithQuestions(int $sectionId): ?array
    {
        // Get section
        $section = $this->getSectionById($sectionId);
        
        if (!$section) {
            return null;
        }
        
        // Get questions
        $sql = "SELECT * FROM audit_questions 
                WHERE section_id = ? 
                ORDER BY position ASC";
        
        $questions = $this->fetchAll($sql, [$sectionId]);
        $section['questions'] = $questions ?: [];
        
        return $section;
    }

    /**
     * Get all sections with questions for an audit
     * 
     * @param int $auditId Audit ID
     * @return array|null Array of sections with questions or null if none found
     */
    public function getFullAuditStructure(int $auditId): ?array
    {
        $sql = "SELECT s.*, q.*
                FROM {$this->table} s
                LEFT JOIN audit_questions q ON s.section_id = q.section_id
                WHERE s.audit_id = ?
                ORDER BY s.section_id, q.question_id";
        
        $results = $this->fetchAll($sql, [$auditId]);
        
        if (!$results) {
            return null;
        }
        
        // Organize results into sections with nested questions
        $sections = [];
        foreach ($results as $row) {
            $sectionId = $row['section_id'];
            
            // Add section if not already added
            if (!isset($sections[$sectionId])) {
                $sections[$sectionId] = [
                    'section_id' => $row['section_id'],
                    'audit_id' => $row['audit_id'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'weight' => $row['weight'],
                    'questions' => []
                ];
            }
            
            // Add question if it exists
            if (!empty($row['question_id'])) {
                $sections[$sectionId]['questions'][] = [
                    'question_id' => $row['question_id'],
                    'section_id' => $row['section_id'],
                    'question_text' => $row['question_text'],
                    'guidance_notes' => $row['guidance_notes'],
                    'question_type' => $row['question_type'],
                    'required' => $row['required'],
                    'options' => $row['options'],
                    'weight' => $row['weight']
                ];
            }
        }
        
        return array_values($sections);
    }

    /**
     * Get all sections with questions and responses for an audit
     * 
     * @param int $auditId Audit ID
     * @return array|null Array of sections with questions and responses or null if none found
     */
    public function getFullAuditStructureWithResponses(int $auditId): ?array
    {
        $sections = $this->getSectionsByAuditId($auditId);
        
        if (!$sections) {
            return null;
        }
        
        // Get questions and responses for each section
        $auditQuestionModel = new AuditQuestion();
        $auditResponseModel = new AuditResponse();
        
        foreach ($sections as &$section) {
            $questions = $auditQuestionModel->getQuestionsBySectionId($section['section_id']) ?: [];
            
            // Get responses for each question
            foreach ($questions as &$question) {
                $response = $auditResponseModel->getResponseByQuestionId($auditId, $question['question_id']);
                if ($response) {
                    $question['response'] = $response['response'];
                    $question['comments'] = $response['comments'];
                }
            }
            
            $section['questions'] = $questions;
        }
        
        return $sections;
    }

    /**
     * Execute a query and return a single record
     * 
     * @param string $sql The SQL query
     * @param array $params Query parameters
     * @return array|null Record or null if not found
     */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->query($sql, $params);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("Error in fetchOne: " . $e->getMessage());
            return null;
        }
    }
} 