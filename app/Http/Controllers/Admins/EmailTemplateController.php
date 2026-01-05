<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    /**
     * Danh sách templates
     */
    public function index()
    {
        $templates = EmailTemplate::orderBy('name')->get();

        return view('admins.email-templates.index', compact('templates'));
    }

    /**
     * Form tạo/sửa template
     */
    public function edit(EmailTemplate $emailTemplate)
    {
        return view('admins.email-templates.edit', compact('emailTemplate'));
    }

    /**
     * Tạo template mới
     */
    public function create()
    {
        $emailTemplate = new EmailTemplate;

        return view('admins.email-templates.edit', compact('emailTemplate'));
    }

    /**
     * Lưu template
     */
    public function store(Request $request)
    {
        $request->validate([
            'key' => ['required', 'string', 'unique:email_templates,key'],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'variables' => ['nullable', 'array'],
        ]);

        EmailTemplate::create($request->only(['key', 'name', 'subject', 'body', 'variables', 'is_active']));

        return redirect()->route('admin.email-templates.index')
            ->with('success', 'Đã tạo template mới.');
    }

    /**
     * Cập nhật template
     */
    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $request->validate([
            'key' => ['required', 'string', 'unique:email_templates,key,'.$emailTemplate->id],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'variables' => ['nullable', 'array'],
        ]);

        $emailTemplate->update($request->only(['key', 'name', 'subject', 'body', 'variables', 'is_active']));

        return redirect()->route('admin.email-templates.index')
            ->with('success', 'Đã cập nhật template.');
    }

    /**
     * Xóa template
     */
    public function destroy(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();

        return back()->with('success', 'Đã xóa template.');
    }

    /**
     * Toggle active
     */
    public function toggle(EmailTemplate $emailTemplate)
    {
        $emailTemplate->update(['is_active' => ! $emailTemplate->is_active]);

        return back()->with('success', 'Đã cập nhật trạng thái template.');
    }
}
