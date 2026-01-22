# Feature: AI Coach

## Executive Summary

The AI Coach is an intelligent training companion that helps users plan, optimize, and adapt their workout schedules. It provides personalized guidance through natural conversation, asks clarifying questions to understand user intent, and suggests specific workout modifications based on performance data and user goals. The AI has no autonomous authority—all schedule changes require explicit user approval.

**Key Capabilities:**
- Natural language conversation about training goals and planning
- Context-aware coaching using workout history, ratings, and past conversations
- Workout schedule analysis and modification suggestions (create, modify, delete, reschedule)
- Multiple chat sessions for different training contexts (event preparation, injury recovery, etc.)
- Complete workout generation with detailed steps (warmup, intervals, cooldown)

**Technical Approach:**
- Direct Claude API (Sonnet 4.5) integration from Laravel backend
- Conversation persistence in application database
- Streaming responses for real-time interaction
- Token-optimized context management

## Problem Statement

Athletes often lack the knowledge to structure effective training plans, especially when:
1. Training for a specific event with time-bound goals and periodization needs
2. Recovering from injury and needing safe, progressive workout adjustments
3. Interpreting performance data (RPE, feeling ratings) to inform future training
4. Balancing training stress with recovery needs

The AI Coach solves this by providing expert guidance through conversation, analyzing workout performance, and generating concrete schedule modifications that users can review and approve.

## User Personas

### Persona 1: Event-Focused Athlete
**Profile:** An athlete training for a specific event (marathon, triathlon, race)
**Needs:**
- Structured training plan leading up to event date
- Progressive workload management
- Taper strategy before event
- Adaptation based on training response (RPE/feeling ratings)

**Example Scenario:**
"I'm running a marathon in 12 weeks. I currently run 30 km per week. Can you help me build a training plan?"

### Persona 2: Injury Recovery Athlete
**Profile:** An athlete returning from injury who needs careful progression
**Needs:**
- Conservative workout intensity and volume
- Safety-first approach to training progression
- Monitoring for warning signs in performance data
- Flexibility to adjust based on recovery response

**Example Scenario:**
"I'm recovering from a knee injury. My physio cleared me for light running. Can you help me build back safely?"

## User Stories

### Core Conversation
- As a user, I want to open a chat with my AI coach from anywhere in the app so I can get guidance whenever I need it
- As a user, I want to start different chat sessions for different training contexts so my conversations stay organized
- As a user, I want the AI to remember our previous conversations so I don't have to repeat context
- As a user, I want the AI to ask clarifying questions so it understands my goals and constraints
- As a user, I want to see AI responses stream in real-time so the experience feels conversational

### Workout Schedule Modifications
- As a user, I want the AI to suggest new workouts to add to my schedule so I can follow a structured plan
- As a user, I want the AI to suggest modifications to existing workouts when my performance indicates I need adjustments
- As a user, I want the AI to suggest deleting or rescheduling workouts when appropriate for my training plan
- As a user, I want to preview any suggested workout before it's added to my schedule so I can verify it makes sense
- As a user, I want to request modifications to the AI's suggestions before approving them so I have control over my schedule
- As a user, I want the AI to create complete running workouts with detailed steps (warmup, intervals, cooldown) so I know exactly what to do

### Context & Personalization
- As a user, I want the AI to analyze my recent workout performance (RPE, feeling ratings) so its suggestions are based on my actual response to training
- As a user, I want the AI to know about my upcoming scheduled workouts so it can make contextually relevant suggestions
- As a user, I want the AI to access my workout history so it understands my current fitness level and training patterns

### Safety & Control
- As a user recovering from injury, I want to see a disclaimer about medical clearance so I'm reminded to train responsibly
- As a user, I want all AI-suggested changes to require my explicit approval so I maintain control over my training
- As a user, I want clear confirmation when changes are applied to my schedule so I know the action was completed

## Functional Requirements

### 1. Chat Interface

#### 1.1 Chat Session Management
**Must Have:**
- Users can create multiple chat sessions
- Each chat session has a default title (e.g., "Chat - January 22, 2026")
- Users can view a list of their chat sessions
- Users can delete chat sessions they no longer need
- Users can open and continue existing chat sessions
- Maximum 5 active chat sessions per user

**Optional Enhancements:**
- Users can rename chat sessions to meaningful titles (e.g., "Marathon Training Plan")
- Users can archive old chat sessions instead of deleting

#### 1.2 Chat Access & UI
**Must Have:**
- Global button/icon accessible from anywhere in the app
- Clicking the button opens a modal/drawer with the chat interface
- Modal shows list of existing chat sessions and option to start new chat
- Clean, mobile-responsive chat interface
- Support for both light and dark mode (following existing app patterns)

#### 1.3 Message Interaction
**Must Have:**
- Text input field for user messages
- Send button and Enter key support for sending messages
- AI responses stream in word-by-word (real-time streaming)
- Typing indicator while AI is composing response
- Loading state while waiting for AI response to begin
- Display of both user and AI messages in conversation flow
- Timestamps on messages
- Clear visual distinction between user and AI messages
- Markdown formatting support in AI responses (bold, lists, code blocks, etc.)

**Optional Enhancements:**
- Ability to cancel an in-progress AI request
- Estimated response time indicator for longer requests

#### 1.4 Conversation Starters
**Must Have:**
- When starting a new chat, show preset prompts to help users begin:
  - "I'm training for a race/event"
  - "I'm recovering from an injury"
  - "I need help with my workout schedule"
  - "Analyze my recent training performance"
- Users can click a starter or type their own message

### 2. AI Context & Memory

#### 2.1 Conversation Context
**Must Have:**
- AI has access to reasonable number of recent messages from current conversation (suggest 50-100 messages or last 4,096 tokens of conversation, whichever is more restrictive)
- AI has access to summaries of previous conversations in other chat sessions
  - Only include most significant previous conversations (suggest top 3-5 by recency or relevance)
  - Summaries should be concise (200-500 tokens each)
- Context is loaded when user opens a chat session

#### 2.2 User & Workout Data Context
**Must Have:**
- AI has access to user's workout history from the last month, including:
  - Workout date, sport, duration
  - All steps within workouts (duration, intensity, targets)
  - RPE ratings (1-10 scale)
  - Feeling ratings (1-5 scale)
  - Any notes or comments on workouts
- AI has access to all upcoming scheduled workouts (no time limit)
- AI has access to user profile data:
  - Name
  - Any fitness level indicators (if available)
  - Training goals (if stored)
  - Injury status or notes (if stored)

#### 2.3 Context Optimization
**Must Have:**
- System intelligently manages token usage to stay within API limits
- Older messages from current conversation are truncated if conversation becomes very long
- Workout data is summarized if needed to fit context window
- System prioritizes most recent and most relevant information

### 3. Workout Schedule Modifications

#### 3.1 Types of Modifications
**Must Have:**
- AI can suggest creating new workouts and adding them to the schedule
- AI can suggest modifying existing workouts (change date, duration, steps, intensity)
- AI can suggest deleting workouts from the schedule
- AI can suggest rescheduling workouts to different dates
- AI can suggest adjusting rest days (adding or removing)
- AI can create or modify workouts in the past (to reflect what actually happened vs. what was planned)

#### 3.2 Workout Detail Level
**Must Have:**
- For running workouts: AI creates complete workouts with detailed steps
  - Warmup phase (duration, intensity/pace)
  - Main workout (intervals, tempo, steady state, etc. with specific durations and intensities)
  - Cooldown phase (duration, intensity/pace)
  - Each step includes: duration, intensity/pace targets, and description
- For other sports (Strength, Cardio, HIIT): AI creates workouts with comprehensive notes explaining:
  - How the workout is structured (exercises, sets, reps, rest periods)
  - What tools/equipment are required (dumbbells, resistance bands, bodyweight, etc.)
  - Exercise descriptions and form cues
  - Progression/regression options if applicable
- AI explains the reasoning/purpose behind suggested workout structure

#### 3.3 Modification Proposal Flow
**Must Have:**
- AI proposes changes one workout at a time
- When proposing a modification, AI clearly states:
  - What change is being suggested (create, modify, delete, reschedule)
  - Which date the workout is for
  - Complete details of the workout (if create/modify)
  - Reasoning for the suggestion
- AI presents this in a conversational but structured format

#### 3.4 Approval Workflow
**Must Have:**
- After AI proposes a workout modification, system shows a preview/review interface:
  - Summary of the change being proposed
  - Full workout details displayed clearly (all steps, durations, intensities)
  - "Apply to Schedule" button
  - "Request Changes" option (user can ask for modifications in chat)
  - "Reject" or "Cancel" option
- User can request modifications to the suggestion via chat before approving
  - Example: "Can you make the warmup 10 minutes instead of 5?"
  - AI updates the proposal and shows new preview
- When user clicks "Apply to Schedule":
  - Changes are immediately applied to the user's workout schedule
  - Confirmation message shown in chat: "Workout added/modified/deleted successfully"
  - User can optionally navigate to calendar to see the change
- Only one pending proposal at a time (user must approve/reject before AI suggests next change)

#### 3.5 Constraints & Validation
**Must Have:**
- No arbitrary constraints on number of workouts per week (AI uses coaching judgment)
- AI can suggest workouts on any date (past, present, future)
- Completed workouts CAN be modified (user may need to update actual workout vs. planned)
- Past workouts CAN be created (user may have done workout but forgot to log it)
- Sport-specific workout rules are AI's responsibility to follow (no hard system constraints)

#### 3.6 Safety & Disclaimers
**Must Have:**
- When user indicates injury recovery context, AI must include a disclaimer in its response:
  - "Important: This is AI-generated guidance. Please ensure you have medical clearance before following any training suggestions, especially when recovering from injury. Consult your healthcare provider or physiotherapist if you experience pain or discomfort."
- Disclaimer should appear once per conversation session where injury is mentioned
- System does not enforce intensity caps or other hard limits for injury recovery (relies on AI judgment and user discretion)

### 4. Rate Limiting & Usage Controls

**Must Have:**
- Maximum 25 messages per user per day
- Clear error message when limit is reached: "You've reached your daily message limit (25 messages). Your limit will reset at midnight."
- Message count resets at midnight user's local timezone (or UTC if timezone unknown)
- Rate limit applies across all chat sessions for a user
- System tracks message count in database

**Optional Enhancements:**
- Admin interface to adjust rate limits per user or globally
- Warning when user approaches limit (e.g., "5 messages remaining today")

### 5. Error Handling

#### 5.1 API Failures
**Must Have:**
- If Claude API is unavailable or returns an error:
  - Display error message in chat interface: "I'm having trouble connecting right now. Please try again in a moment."
  - Allow user to retry the message
  - Log error for debugging
- If API response is incomplete or malformed:
  - Show graceful error message
  - Do not save incomplete response to conversation history

#### 5.2 Nonsensical or Inappropriate Responses
**Must Have:**
- Rely on preview/approval workflow to catch nonsensical workout suggestions
- User can reject proposal and ask for different suggestion
- User can provide feedback in chat to guide AI toward better response

**Out of Scope:**
- Formal reporting or flagging mechanism for problematic responses
- Automated content moderation or filtering

#### 5.3 Concurrent Editing Conflicts
**Must Have:**
- If user manually edits a workout in the calendar while AI is suggesting changes to the same workout:
  - Use "last write wins" approach (most recent change is applied)
  - When AI proposal is approved, it overwrites any manual changes made in the meantime
  - Optional: Show warning if detected: "This workout was recently modified. Applying this change will overwrite your recent edits."

**Reasonable Default:**
- No complex conflict resolution for v1
- Conflicts should be rare since proposals are one-at-a-time and require approval

### 6. Technical Implementation

#### 6.1 Architecture
**Must Have:**
- Laravel backend integration with Claude API (Anthropic API)
- Use Claude Sonnet 4.5 model (model ID: `claude-sonnet-4-5-20250929`)
- Direct API calls from Laravel (no MCP server)
- Conversation history stored in application database
- API key stored securely in environment variables (`.env` file, never committed)

#### 6.2 API Integration
**Must Have:**
- Use Anthropic's official PHP SDK or HTTP client (Guzzle/Laravel HTTP)
- Implement streaming responses using Server-Sent Events (SSE) or similar
- Frontend consumes stream and displays word-by-word
- Proper error handling for API failures, timeouts, rate limits
- Token counting and context window management (Sonnet 4.5 has 200k context window)

#### 6.3 System Prompts & Instructions
**Must Have:**
- System prompt that defines AI's role as a knowledgeable but cautious coach
- Instructions for AI to:
  - Ask clarifying questions before making suggestions
  - Propose changes one workout at a time
  - Always explain reasoning behind suggestions
  - Format workout proposals in a consistent, parseable structure
  - Include injury disclaimer when appropriate
  - Respect user's goals and constraints
- System prompt includes context about available sports, workout structure, RPE/feeling scales

#### 6.4 Conversation Management
**Must Have:**
- Conversation summarization strategy for previous chats:
  - Use Claude API to generate summary of completed conversations
  - Trigger summarization when user closes a chat session (immediate)
  - OR via background job that runs every hour for unsummarized sessions
  - Use Laravel queued jobs for asynchronous processing
  - Store summary in database alongside conversation
  - Include summary in context when starting new conversations
- Token budget allocation:
  - Reserve tokens for system prompt (~1,000 tokens)
  - Reserve tokens for user/workout data context (~10,000-20,000 tokens)
  - Reserve tokens for conversation history (~20,000-40,000 tokens)
  - Reserve tokens for AI response generation (~4,000-8,000 tokens)
  - Monitor and adjust based on actual usage

## Data Model Requirements

### New Database Tables

#### `coach_chat_sessions`
- `id` (primary key)
- `user_id` (foreign key to users table)
- `title` (string, default: "Chat - {created_at formatted}")
- `summary` (text, nullable - stored summary of conversation for context)
- `is_archived` (boolean, default: false) - optional for future
- `created_at` (timestamp)
- `updated_at` (timestamp)
- `deleted_at` (timestamp, nullable - for soft deletes)

**Indexes:**
- `user_id`
- `created_at` (for sorting)

**Relationships:**
- Belongs to User
- Has many CoachMessages

#### `coach_messages`
- `id` (primary key)
- `coach_chat_session_id` (foreign key to coach_chat_sessions)
- `role` (enum: 'user', 'assistant', 'system')
- `content` (text - the message content)
- `metadata` (json, nullable - for storing additional data like token count, model version, etc.)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Indexes:**
- `coach_chat_session_id`
- `created_at` (for ordering messages)

**Relationships:**
- Belongs to CoachChatSession

#### `coach_workout_proposals`
- `id` (primary key)
- `coach_chat_session_id` (foreign key to coach_chat_sessions)
- `coach_message_id` (foreign key to coach_messages - the AI message that contains this proposal)
- `proposal_type` (enum: 'create', 'modify', 'delete', 'reschedule')
- `workout_id` (foreign key to workouts table, nullable - null for 'create', populated for modify/delete/reschedule)
- `proposed_data` (json - full workout data being proposed)
- `status` (enum: 'pending', 'approved', 'rejected', 'modified')
- `applied_at` (timestamp, nullable)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Indexes:**
- `coach_chat_session_id`
- `coach_message_id`
- `workout_id`
- `status`

**Relationships:**
- Belongs to CoachChatSession
- Belongs to CoachMessage
- Belongs to Workout (nullable)

#### `coach_message_daily_counts`
- `id` (primary key)
- `user_id` (foreign key to users table)
- `date` (date)
- `message_count` (integer, default: 0)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Indexes:**
- Unique index on (`user_id`, `date`)
- `date` (for cleanup of old records)

**Relationships:**
- Belongs to User

### Modifications to Existing Tables

**No modifications required to existing Workout, Step, or User tables.**

The AI coach works with existing workout structure without requiring schema changes.

## API / Integration Requirements

### Anthropic Claude API

#### Authentication
- API key required from Anthropic (https://console.anthropic.com/)
- Store in `.env` as `ANTHROPIC_API_KEY`
- Never expose API key to frontend

#### Endpoints Used
- `POST https://api.anthropic.com/v1/messages` - for message completion
- Support for streaming responses (Server-Sent Events)

#### Request Format
```json
{
  "model": "claude-sonnet-4-5-20250929",
  "max_tokens": 4096,
  "system": "You are a knowledgeable fitness coach...",
  "messages": [
    {"role": "user", "content": "Help me train for a marathon"},
    {"role": "assistant", "content": "I'd be happy to help..."}
  ],
  "stream": true
}
```

#### Response Handling
- Parse streaming responses and forward to frontend via SSE or WebSockets
- Handle API errors gracefully
- Respect rate limits from Anthropic
- Monitor token usage and costs

#### Context Window Management
- Claude Sonnet 4.5: 200,000 token context window
- Implement smart truncation if conversation exceeds limits
- Prioritize recent messages and relevant workout data

### Frontend-Backend Communication

#### Real-time Messaging
- WebSockets (Laravel Reverb/Pusher) OR Server-Sent Events for streaming AI responses
- Standard HTTP for user message submission and chat management

#### Internal API Endpoints
**Note:** The Coach API should only be accessible internally to the system, not directly to users. All interactions happen through Livewire components which internally call service classes that interact with the AI. No public API routes should be exposed.

**Internal Service Methods:**
- `CoachingService::createSession(User $user)` - Create new chat session
- `CoachingService::getUserSessions(User $user)` - List user's chat sessions
- `CoachingService::getSession(CoachChatSession $session)` - Get specific chat session with messages
- `CoachingService::deleteSession(CoachChatSession $session)` - Delete chat session
- `CoachingService::sendMessage(CoachChatSession $session, string $message)` - Send user message, trigger AI response
- `CoachingService::approveProposal(CoachWorkoutProposal $proposal)` - Approve a workout proposal on behalf of user
- `CoachingService::rejectProposal(CoachWorkoutProposal $proposal)` - Reject a workout proposal
- `CoachingService::getRateLimitStatus(User $user)` - Check current message count for user

## User Interface Requirements

**Note:** Use Flux UI Pro components whenever a suitable component exists. Follow existing application patterns for consistency.

### Global Chat Button
- Fixed position button (bottom-right corner recommended) - use Flux button component
- Icon: Message/chat bubble icon - use Flux icon component
- Opens chat modal when clicked
- Accessible via keyboard (tab navigation, Enter to activate)

### Chat Modal/Drawer
**Layout:**
- Use Flux modal component
- Full-height modal (mobile: full screen, desktop: 600px wide drawer from right side)
- Header with:
  - Current chat session title
  - Back button to return to session list
  - Close button to dismiss modal
- Message area:
  - Scrollable container showing conversation history
  - Auto-scroll to bottom on new messages
  - User messages aligned right, AI messages aligned left
  - Timestamps on messages (relative time: "2 minutes ago" or absolute)
  - Typing indicator when AI is responding
- Input area:
  - Text input field (textarea that expands with content) - use Flux textarea component
  - Send button - use Flux button component
  - Character/message count indicator (optional)
  - Rate limit warning if approaching daily limit

**Session List View:**
- Shown when user first opens chat modal (if multiple sessions exist)
- "New Chat" button at top
- List of existing chat sessions:
  - Session title
  - Last message preview (truncated)
  - Last updated timestamp
  - Click to open session
  - Swipe/long-press to delete (mobile) or delete icon (desktop)

**Conversation Starters:**
- Shown when user creates new chat or opens empty chat
- 4 clickable prompt cards/buttons
- Each prompt is a suggested question/topic
- User can click prompt or ignore and type their own message

### Workout Proposal Preview
**Display:**
- Inline component within the chat message flow
- Card/panel distinct from regular chat messages
- Shows:
  - Proposal type badge (Create, Modify, Delete, Reschedule)
  - Workout date
  - Sport icon/label
  - If create/modify: Complete workout details
    - Overall duration
    - Each step with duration, intensity, description
    - Target pace/heart rate (if applicable)
  - AI's reasoning/explanation for the suggestion
- Action buttons (use Flux button components):
  - Primary: "Apply to Schedule" (prominent, variant="primary")
  - Secondary: "Request Changes" (opens input field to ask for modifications)
  - Tertiary: "Reject" (subtle, variant="ghost")
- Loading state when applying changes (use wire:loading)
- Confirmation state after successful application

**Responsive Design:**
- Mobile: Stack workout details vertically
- Desktop: Use grid/columns for workout steps
- Ensure all workout details are readable without horizontal scrolling

### Loading & Error States

**While AI is thinking:**
- Typing indicator (animated dots)
- Text: "Coach is thinking..."

**While streaming response:**
- Text appears word-by-word
- Cursor/caret at end of streaming text

**API Error:**
- Error message appears as system message in chat
- Option to retry
- Do not save error message to conversation history

**Rate Limit Reached:**
- Clear message in chat input area
- Disable send button
- Show countdown to reset time (optional)

### Dark Mode Support
- Follow existing app patterns for dark mode
- Ensure chat interface works in both light and dark themes
- Special attention to message bubbles, proposal cards, buttons

## Security & Authorization

### Authentication
- All AI coach endpoints require authenticated user
- Use Laravel Sanctum or existing session-based auth
- No guest/anonymous access to AI coach

### Authorization
- Users can only access their own chat sessions
- Users can only view/modify their own workouts through AI suggestions
- Enforce user ownership checks on all endpoints (use policies)

### Data Privacy
- Conversation data is private to each user
- Do not share one user's workout data or conversations with another user
- API calls to Claude API should not include personally identifiable information beyond what's necessary (use user ID, not email/name in logs)

### API Key Security
- Anthropic API key stored in `.env` file
- Never expose API key to frontend/browser
- Never commit API key to version control
- Rotate key if compromised

### Rate Limiting
- 25 messages per user per day (enforced at application level)
- Additional server-side rate limiting to prevent abuse (e.g., max 10 requests per minute per user)
- Respect Anthropic's rate limits and handle gracefully

### Input Validation
- Validate all user messages before sending to API (max length, content type)
- Sanitize user input to prevent injection attacks
- Validate workout proposal data before applying to schedule
- Use Laravel Form Requests for validation

## Validation Rules

### Chat Session
- `title`: optional, string, max 255 characters
- User can only create chat sessions for themselves

### Messages
- `content`: required, string, min 1 character, max 10,000 characters
- Strip excessive whitespace
- Prevent empty messages

### Workout Proposals
- `proposal_type`: required, must be one of: create, modify, delete, reschedule
- `proposed_data`: required for create/modify, must be valid JSON matching workout structure
- Validate that proposed workout data matches expected schema:
  - `sport`: required, must be valid sport in system
  - `scheduled_for`: required, must be valid date
  - `steps`: required for running workouts, array of step objects
    - Each step: `duration`, `intensity`, `description` required
- For modify/delete/reschedule: `workout_id` must exist and belong to user

### Rate Limiting
- Enforce 25 messages per user per day
- Track at message creation time
- Return clear validation error when limit exceeded

## Edge Cases

### 1. User Deletes Workout While Proposal is Pending
**Scenario:** AI proposes modification to workout #123. While proposal is pending approval, user manually deletes workout #123 from calendar.

**Handling:** When user tries to approve proposal, system checks if workout still exists. If not, show error: "This workout no longer exists. It may have been deleted." Proposal is automatically rejected.

### 2. Very Long Conversation Exceeds Context Window
**Scenario:** User has 200+ messages in a single chat session, exceeding token limits.

**Handling:**
- System automatically truncates oldest messages to fit within context window
- Keep system prompt, recent messages (last 50), and user/workout data
- Show optional notice to user: "This is a long conversation. Earlier messages may not be visible to the coach."

### 3. User Sends Rapid Successive Messages
**Scenario:** User sends multiple messages quickly before AI responds to first one.

**Handling:**
- Queue messages and process sequentially
- Show typing indicator after first message is sent
- Do not allow sending new message until AI has responded (disable send button during active request)
- Alternative: Allow queuing but count all queued messages against rate limit

### 4. AI Suggests Workout on Date That Already Has Multiple Workouts
**Scenario:** User already has 2 workouts scheduled on Monday. AI suggests adding a third workout on Monday.

**Handling:**
- No system constraint; allow multiple workouts per day
- AI should be aware of existing workouts (they're in context) and use coaching judgment
- User can reject if they don't want another workout that day

### 5. User's Local Timezone vs. Server Timezone
**Scenario:** User in US sends 25 messages. At 11:50 PM local time (but next day in UTC), they try to send another message.

**Handling:**
- Store user's timezone in user profile for display purposes
- All dates/times stored in database are UTC (workout scheduled_at, message created_at, etc.)
- Convert to user's timezone for display only
- Use user's local timezone for daily rate limit reset (midnight in user's timezone)
- If timezone unknown, default to UTC

### 6. Browser Closes During Streaming Response
**Scenario:** AI is streaming a long response, user closes browser or loses connection.

**Handling:**
- Backend continues generating response and saves complete message to database
- When user reopens chat, they see the complete message
- Do not save partial/incomplete responses

### 7. User Requests Impossible Workout
**Scenario:** User asks: "Give me a 30-second marathon training plan."

**Handling:**
- AI should recognize unrealistic request and ask clarifying questions
- AI should use coaching judgment to educate user
- No system-level constraints; rely on AI's reasoning

### 8. Concurrent Proposals
**Scenario:** User opens chat in two browser tabs. In tab 1, AI proposes workout A. In tab 2, user sends message and AI proposes workout B.

**Handling:**
- System enforces one active proposal at a time per session
- Second proposal creation fails or waits until first is resolved
- Frontend shows error: "Please respond to the pending workout suggestion before requesting more changes."

### 9. Chat Session Deleted While User is Viewing It
**Scenario:** User has chat open in browser. Meanwhile, chat session is deleted (maybe by user in another tab, or by admin).

**Handling:**
- Use soft deletes on chat sessions
- If user tries to send message to deleted session, show error: "This chat session has been deleted."
- Refresh session list

### 10. AI Response Contains Workout Proposal But Doesn't Follow Expected Format
**Scenario:** AI response includes workout suggestion but in unexpected JSON structure.

**Handling:**
- Backend parses AI response looking for workout proposals
- If structure doesn't match expected format, do not create proposal object
- User sees AI message but no approval interface
- Log parsing failure for debugging
- User can ask AI to reformat or provide workout details again

## Acceptance Criteria

### Chat Interface
- [ ] User can open chat modal from any page via global button
- [ ] User can create a new chat session
- [ ] User is limited to maximum 5 active chat sessions
- [ ] When limit is reached, user must delete an old chat to create a new one
- [ ] User can view list of existing chat sessions
- [ ] User can open and continue an existing chat session
- [ ] User can delete a chat session
- [ ] Deleted chat sessions are removed from list
- [ ] Chat interface displays user and AI messages in conversation flow
- [ ] Messages include timestamps
- [ ] Chat interface works in both light and dark mode
- [ ] Chat modal is responsive on mobile and desktop
- [ ] Chat interface uses Flux UI components where applicable

### Conversation Starters
- [ ] New chat shows 4 preset conversation starter prompts
- [ ] User can click a starter to send that message
- [ ] User can ignore starters and type their own message
- [ ] Starters are contextually relevant (training, injury, schedule help, performance analysis)

### Messaging & Streaming
- [ ] User can type and send messages
- [ ] Typing indicator appears while AI is composing response
- [ ] AI responses stream in word-by-word
- [ ] AI responses render markdown formatting (bold, lists, code blocks, etc.)
- [ ] User cannot send new message while AI is responding (send button disabled)
- [ ] Enter key sends message (with Shift+Enter for new line)
- [ ] Messages auto-scroll to bottom as conversation continues

### AI Context & Memory
- [ ] AI has access to last month of workout history including all details (date, sport, steps, RPE, feeling)
- [ ] AI has access to all upcoming scheduled workouts
- [ ] AI has access to recent messages from current conversation
- [ ] AI has access to summaries of previous conversations
- [ ] AI can reference past workouts and conversations in responses
- [ ] Context is loaded when chat session is opened
- [ ] Conversation summaries are generated when user closes chat session
- [ ] Hourly background job summarizes any unsummarized chat sessions
- [ ] Summaries are stored in database and used as context for future conversations

### Workout Proposal - Creation
- [ ] AI can suggest creating a new workout
- [ ] For running workouts, AI creates complete workout with warmup, main workout, and cooldown steps
- [ ] Each step includes duration, intensity/pace, and description
- [ ] For non-running workouts (Strength, Cardio, HIIT), AI provides comprehensive notes explaining workout structure and required tools/equipment
- [ ] AI explains reasoning behind the workout structure
- [ ] Proposal is presented one workout at a time

### Workout Proposal - Preview & Approval
- [ ] When AI proposes a workout, preview interface is shown in chat
- [ ] Preview shows all workout details (date, sport, steps, durations, intensities)
- [ ] Preview includes "Apply to Schedule" button
- [ ] Preview includes "Request Changes" option
- [ ] Preview includes "Reject" button
- [ ] User can request modifications via chat before approving
- [ ] AI updates proposal based on requested changes
- [ ] When user clicks "Apply to Schedule," workout is added to their schedule
- [ ] Confirmation message appears after successful application
- [ ] Only one pending proposal at a time

### Workout Proposal - All Types
- [ ] AI can suggest creating new workouts (tested and working)
- [ ] AI can suggest modifying existing workouts (tested and working)
- [ ] AI can suggest deleting workouts (tested and working)
- [ ] AI can suggest rescheduling workouts to different dates (tested and working)
- [ ] AI can create/modify workouts in the past (tested and working)

### Workout Proposal - Past Workout UX
- [ ] When AI suggests modifying a past/completed workout, preview shows "Past Workout" badge/indicator
- [ ] Apply button text changes to "Update Past Workout" for past workouts
- [ ] Additional context message explains this is a past workout update
- [ ] Different color treatment (amber/orange) distinguishes past workout proposals from future ones

### Workout Proposal - Data Structure
- [ ] AI responses containing workout proposals use the defined JSON schema
- [ ] Backend successfully parses JSON proposals from AI responses
- [ ] Validation fails gracefully if JSON structure is malformed
- [ ] Non-running workouts include comprehensive notes in the notes field

### Safety & Disclaimers
- [ ] When user mentions injury recovery, AI includes disclaimer about medical clearance
- [ ] Disclaimer appears once per conversation where injury is relevant
- [ ] Disclaimer text is clear and prominent

### Rate Limiting
- [ ] User is limited to 25 messages per day
- [ ] Message count resets at midnight (user's local timezone if available, otherwise UTC)
- [ ] When limit is reached, clear error message is shown
- [ ] Send button is disabled when limit is reached
- [ ] User can see their remaining message count (optional, but helpful)

### Error Handling
- [ ] If Claude API is unavailable, error message appears in chat interface
- [ ] User can retry failed messages
- [ ] If workout proposal references deleted workout, error is shown when user tries to approve
- [ ] Malformed AI responses are handled gracefully without crashing

### Performance & UX
- [ ] AI responses begin streaming within 2-3 seconds of user message
- [ ] Chat interface remains responsive during AI response generation
- [ ] No noticeable lag when opening chat sessions with many messages
- [ ] Loading states are clear and informative

### Authorization & Security
- [ ] Users can only access their own chat sessions
- [ ] Users can only view/modify their own workouts via AI
- [ ] API key is stored securely and not exposed to frontend
- [ ] All chat endpoints require authentication
- [ ] Unauthorized access attempts are blocked
- [ ] Coach API is accessible only internally to the system (via service layer), not directly to users

### Timezone Handling
- [ ] User's timezone is stored in user profile
- [ ] All dates/times in database are stored in UTC
- [ ] Dates/times are converted to user's timezone for display only
- [ ] Rate limit reset uses user's local timezone (midnight in their timezone)
- [ ] System defaults to UTC if user timezone is unknown

## Technical Notes

### Recommended Technology Stack
- **Backend:** Laravel 12
- **API Client:** Anthropic PHP SDK or Laravel HTTP client with Guzzle
- **Real-time Communication:** Laravel Reverb (WebSockets) for streaming AI responses
- **Frontend:** Livewire 3 with Flux UI Pro components (use Flux components whenever suitable component exists)
- **Database:** Existing MySQL/PostgreSQL with new tables for chat data
- **Caching:** Redis for rate limiting and conversation summaries

### Implementation Phases

**Phase 1: Core Chat Infrastructure** (Week 1-2)
- Database migrations for chat sessions, messages, proposals
- Basic chat interface with Livewire component
- Claude API integration (non-streaming)
- Message sending and receiving
- Rate limiting implementation

**Phase 2: Context & Memory** (Week 2-3)
- Workout data context integration
- Conversation history management
- Token optimization and summarization
- System prompt engineering

**Phase 3: Workout Proposals** (Week 3-4)
- Workout proposal parsing from AI responses
- Preview/approval interface
- Apply changes to workout schedule
- Modification request workflow

**Phase 4: Streaming & Polish** (Week 4-5)
- Implement streaming responses
- Conversation starters
- Loading states and animations
- Error handling improvements
- Dark mode support
- Mobile responsiveness

**Phase 5: Testing & Refinement** (Week 5-6)
- Comprehensive testing (unit, feature, browser tests)
- Edge case handling
- Performance optimization
- Documentation
- User acceptance testing

### Performance Considerations
- **Token Usage:** Monitor and optimize to control API costs. Claude Sonnet 4.5 pricing is ~$3 per million input tokens, ~$15 per million output tokens (as of early 2025).
- **Response Time:** Streaming improves perceived performance. First token should arrive within 1-2 seconds.
- **Database Queries:** Use eager loading when fetching chat sessions with messages. Index foreign keys for performance.
- **Conversation Summarization:** Run as background job (queued) to avoid blocking user requests.
- **Rate Limit Storage:** Use Redis for fast rate limit checking if available, fallback to database.

### Testing Strategy
- **Unit Tests:** Test individual components (rate limiter, context builder, proposal parser)
- **Feature Tests:** Test API endpoints, conversation flow, workout proposal application
- **Browser Tests (Pest v4):** Test full user journey (create chat, send message, approve workout proposal)
- **Manual Testing:** Test AI responses for quality, coherence, and adherence to coaching guidelines

### Cost Estimation (Monthly)
Assumptions:
- 100 active users
- Average 10 messages per user per day
- Average message: 200 tokens input (user message + context), 500 tokens output (AI response)

**Calculation:**
- 100 users × 10 messages/day × 30 days = 30,000 messages/month
- Input tokens: 30,000 × 200 = 6 million tokens = $18
- Output tokens: 30,000 × 500 = 15 million tokens = $225
- **Total: ~$243/month**

Scale accordingly based on actual user base and usage patterns. Consider implementing usage analytics to monitor costs.

### System Prompt Example (Draft)
```
You are an expert fitness coach specializing in endurance training and injury recovery. You help athletes plan and optimize their training schedules through thoughtful conversation and data-driven suggestions.

Your capabilities:
- Analyze workout performance data (RPE ratings 1-10, feeling ratings 1-5)
- Suggest specific workout modifications (create, modify, delete, reschedule)
- Create detailed running workouts with warmup, intervals/main work, and cooldown
- Ask clarifying questions to understand user goals and constraints
- Provide evidence-based coaching advice

When suggesting workouts:
1. Propose one workout at a time
2. Explain your reasoning clearly
3. For running workouts, include specific steps with durations, paces, and descriptions
4. For non-running workouts (Strength, Cardio, HIIT), provide comprehensive notes explaining structure and required equipment
5. Format proposals using the JSON schema defined in the requirements (see question #3 for exact structure)

When users mention injury:
- Include this disclaimer: "Important: This is AI-generated guidance. Please ensure you have medical clearance..."
- Prioritize safety and conservative progression

Available sports: Running, Strength, Cardio, HIIT
RPE scale: 1 (very easy) to 10 (maximum effort)
Feeling scale: 1 (terrible) to 5 (great)

Always be supportive, knowledgeable, and user-focused. Ask before assuming.
```

### Conversation Summarization Prompt (Draft)
```
Summarize the following coaching conversation in 200-300 words. Focus on:
- User's stated goals and context
- Key insights from workout analysis
- Training plan decisions made
- Any constraints or preferences mentioned
- Current training phase/status

This summary will be used as context for future coaching conversations.

[CONVERSATION HISTORY]
```

## Out of Scope

The following items are explicitly NOT included in the initial version of the AI Coach feature:

### Reporting & Content Moderation
- User reporting or flagging of problematic AI responses
- Automated content filtering or moderation systems
- Admin review of AI conversations for quality

### Advanced Chat Features
- Chat session archiving (only delete available)
- Search within chat history
- Exporting chat transcripts
- Sharing conversations with other users or coaches
- Notifications when AI responds (browser notifications, badges, etc.)

### Advanced AI Features
- Voice input/output for messages
- Image analysis (uploading workout screenshots, form check photos)
- Integration with wearable devices (Garmin, Strava, etc.) for automatic workout import
- Proactive AI notifications ("You haven't logged a workout in 3 days...")
- AI-initiated conversations

### Multi-User Collaboration
- Sharing workouts or training plans generated by AI with other users
- Coach-to-athlete messaging using the same system
- Group coaching or team training plans

### Advanced Workout Features
- Workout library/templates that AI can reference
- Recurring workout patterns (AI suggests once, doesn't auto-schedule)
- Multi-sport training plan optimization (triathlon, duathlon)
- Periodization visualization or training load graphs

### Billing & Usage Tiers
- Different AI capabilities based on subscription tier
- Pay-per-message or premium AI features
- Usage analytics dashboard for users
- Admin dashboard for monitoring API usage and costs

### Integrations
- Calendar sync (Google Calendar, Apple Calendar)
- Social sharing of AI coaching conversations
- Export to other training platforms

### Advanced Personalization
- Learning user preferences over time beyond conversation history
- Multiple AI "coach personalities" to choose from
- Custom system prompts per user

## Open Questions

~~The following questions remain unresolved and should be addressed before or during implementation:~~

**Note:** All 10 questions have been resolved (marked with ✅). The requirements are complete and ready for implementation.

### 1. Conversation Summarization Timing ✅ RESOLVED
**Decision:** Conversation summaries should be generated:
- When user closes a chat session (immediate trigger)
- OR via background job that runs every hour for any unsummarized sessions
- Use Laravel queued jobs for asynchronous processing

### 2. Chat Session Limit Enforcement ✅ RESOLVED
**Decision:** Maximum 5 active chat sessions per user (hard limit). User must delete an old chat to create a new one when limit is reached.

### 3. Workout Proposal Data Structure ✅ RESOLVED
**Decision:** AI should use the following JSON schema for workout proposals:

**Field Definitions:**
- `proposal_type`: "create", "modify", "delete", or "reschedule"
- `workout_id`: null for create, workout ID (integer) for modify/delete/reschedule
- `workout_data.scheduled_at`: ISO 8601 datetime string in UTC
- `workout_data.sport`: "running", "strength", "cardio", or "hiit"
- `steps[].duration_value`: seconds for time, meters for distance
- `steps[].target_zone`: zone number (1-5) if target_mode is "zone"
- `steps[].target_min/max`: heart rate in bpm or pace in seconds per km if target_mode is "range"
- `steps[].children`: array of nested step objects (for repeat groups)

**Example JSON Structure:**
```json
{
  "proposal_type": "create",
  "workout_id": null,
  "workout_data": {
    "name": "Morning Run",
    "sport": "running",
    "scheduled_at": "2026-01-25T06:00:00Z",
    "notes": "Optional workout notes or instructions",
    "steps": [
      {
        "kind": "warmup",
        "duration_type": "time",
        "duration_value": 600,
        "intensity": "warmup",
        "target_type": "pace",
        "target_mode": "zone",
        "target_zone": 2,
        "target_min": null,
        "target_max": null,
        "description": "Easy warmup pace",
        "children": []
      }
    ]
  },
  "reasoning": "Explanation of why this workout is being suggested"
}
```

**For non-running workouts (Strength, Cardio, HIIT):**
- Steps array may be empty or minimal
- Comprehensive instructions should be in the `notes` field, including:
  - Exercise list with sets/reps
  - Required equipment
  - Form cues and descriptions
  - Progression/regression options

### 4. Handling Workout Steps for Non-Running Sports ✅ RESOLVED
**Decision:** For non-running workouts (Strength, Cardio, HIIT), AI must provide comprehensive notes explaining:
- How the workout is structured (exercises, sets, reps, rest periods)
- What tools/equipment are required
- Exercise descriptions and form cues
- Progression/regression options if applicable

### 5. User Timezone Handling ✅ RESOLVED
**Decision:**
- Store user's timezone in user profile (detect from browser or allow setting in user settings)
- All dates/times stored in database are UTC
- Convert to user's timezone for display purposes only
- Use user's local timezone for daily rate limit reset (midnight in user's timezone)
- If timezone unknown, default to UTC

### 6. Frontend Framework for Chat UI ✅ RESOLVED
**Decision:** Livewire 3 with Flux UI Pro components. Use Flux components whenever a suitable component exists for the UI element being built.

### 7. WebSocket vs. Server-Sent Events for Streaming ✅ RESOLVED
**Decision:** Use Laravel Reverb (WebSockets) for streaming AI responses.

### 8. Notification Strategy ✅ RESOLVED
**Decision:** Notifications are out of scope for v1. Users check chat when they want to see responses.

### 3. Past Workout Modification UX ✅ RESOLVED
**Decision:** When AI suggests modifying a completed or past workout, the preview must clearly indicate this with:
- Visual badge/indicator showing "Past Workout" or "Completed Workout"
- Different text on the apply button: "Update Past Workout" instead of "Apply to Schedule"
- Additional context message in the preview: "This workout is in the past. Apply this change to update the actual workout you performed."
- Different color treatment (subtle warning color like amber/orange) to distinguish from future workouts

### 10. API Cost Monitoring & Alerts ✅ RESOLVED
**Decision:** Admin dashboard and cost monitoring are out of scope for v1. Rely on Anthropic's console for monitoring API usage and costs.

---

## Document Version
- **Version:** 2.0
- **Date:** January 22, 2026
- **Author:** AI Requirements Gathering Session
- **Status:** Complete - Ready for Implementation
- **Changelog:**
  - v1.0: Initial draft with 10 open questions
  - v2.0: All questions resolved, requirements finalized

## Next Steps
1. ✅ ~~Review this document with stakeholders~~ - Complete
2. ✅ ~~Address open questions~~ - All 10 questions resolved
3. Create technical implementation plan and story breakdown
4. Set up Anthropic API account and obtain API key
5. Begin Phase 1 implementation (Core Chat Infrastructure)