# File Editing, Review & Transparency Commitments

## 1. Direct File Editing & Interactive Review Policy
- STRICT PROHIBITION: Do NOT use terminal shell commands (such as cp, mv, cat, or echo) to modify, overwrite, or create project source files.
- MANDATORY USE OF FILE EDITOR TOOLS: Always use direct IDE file editing tools on the target project files.
- INTERACTIVE USER APPROVAL: Ensure the IDE always presents an inline visual diff with floating Accept (Terima) or Reject (Tolak) review buttons before changes are applied.

## 2. Mandatory Halt & Confirmation on Editor Failure
- STRICT RULE: If the visual editor tool encounters any path, permission, or sandbox issue that prevents the interactive Accept/Reject diff dialog from appearing on the user screen, the agent is STRICTLY FORBIDDEN from taking workarounds or executing scripts behind the scenes.
- IMMEDIATE STOP: The agent MUST IMMEDIATELY STOP and directly report the issue to the user, asking for instructions before touching any files.

## 3. Full Transparency & Zero Background Execution
- No code modifications or file changes may be performed silently in the background without the explicit visual knowledge and approval of the user.

## 4. Communication & Terminology Guidelines
- Keep explanations clear, human-readable, and in proper Indonesian.
- Avoid using confusing LaTeX markup (like raw dollar signs, math formatting) in chat responses.
- Explain concepts using practical retail/inventory operations terminology.
