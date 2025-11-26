## Required packages to install:

composer require intervention/image
composer require guzzlehttp/guzzle
composer require php-ffmpeg/php-ffmpeg (optional, for advanced video processing)

For image processing:
- intervention/image handles image manipulation and thumbnail generation

For HTTP requests (Vimeo, YouTube APIs):
- guzzlehttp/guzzle (should already be included in Laravel)

For advanced video processing:
- php-ffmpeg/php-ffmpeg provides PHP wrapper for FFmpeg


# Training Platform API Documentation

## Base URL
`http://localhost:8000/api`

## Authentication
All protected endpoints require Bearer token authentication:
```
Authorization: Bearer {your_token_here}
```

## Endpoints

### Authentication
- POST `/register` - Register new user
- POST `/login` - Login user
- POST `/logout` - Logout user
- GET `/me` - Get current user info
- POST `/forgot-password` - Request password reset
- POST `/reset-password` - Reset password

### Courses
- GET `/courses` - List all published courses
- POST `/courses` - Create new course (Instructor)
- GET `/courses/{id}` - Get course details
- PUT `/courses/{id}` - Update course (Instructor/Admin)
- DELETE `/courses/{id}` - Delete course (Instructor/Admin)
- POST `/courses/{id}/publish` - Publish course
- POST `/courses/{id}/enroll` - Enroll in course

### Chapters
- GET `/courses/{courseId}/chapters` - List chapters
- POST `/courses/{courseId}/chapters` - Create chapter
- GET `/courses/{courseId}/chapters/{id}` - Get chapter
- PUT `/courses/{courseId}/chapters/{id}` - Update chapter
- DELETE `/courses/{courseId}/chapters/{id}` - Delete chapter

### Lessons
- GET `/chapters/{chapterId}/lessons` - List lessons
- POST `/chapters/{chapterId}/lessons` - Create lesson
- GET `/chapters/{chapterId}/lessons/{id}` - Get lesson
- PUT `/chapters/{chapterId}/lessons/{id}` - Update lesson
- DELETE `/chapters/{chapterId}/lessons/{id}` - Delete lesson
- POST `/chapters/{chapterId}/lessons/{id}/complete` - Mark as complete
- POST `/chapters/{chapterId}/lessons/{id}/progress` - Update progress

### Tests
- POST `/tests` - Create test
- GET `/tests/{id}` - Get test
- POST `/tests/{testId}/start` - Start test
- POST `/submissions/{submissionId}/submit` - Submit test
- POST `/submissions/{submissionId}/grade` - Grade submission

### Payments
- GET `/payments/calculate/{courseId}` - Calculate total
- POST `/payments/create` - Create payment
- POST `/payments/{paymentId}/complete` - Complete payment
- GET `/payments/my-payments` - Get user payments

### Dashboards
- GET `/student/dashboard/overview` - Student overview
- GET `/instructor/dashboard/overview` - Instructor overview
- GET `/admin/dashboard/overview` - Admin overview

## Response Format
All responses follow this format:
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {}
}
```

## Error Responses
```json
{
  "success": false,
  "message": "Error message",
  "errors": {}
}
```