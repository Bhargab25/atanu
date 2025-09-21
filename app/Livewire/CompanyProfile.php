<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use App\Models\CompanyProfile as CompanyProfileModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CompanyProfile extends Component
{
    use WithFileUploads, Toast;

    // Tab management
    public $activeTab = 'basic';

    // Company Profile
    public $companyProfile;

    // Basic Information
    public $name = '';
    public $legalName = '';
    public $email = '';
    public $phone = '';
    public $mobile = '';
    public $website = '';

    // Address Information
    public $address = '';
    public $city = '';
    public $state = '';
    public $country = 'India';
    public $postalCode = '';

    // Tax & Legal Information
    public $panNumber = '';
    public $gstin = '';
    public $cin = '';
    public $tanNumber = '';
    public $fssaiNumber = '';
    public $msmeNumber = '';

    // Banking Information
    public $bankName = '';
    public $bankAccountNumber = '';
    public $bankIfscCode = '';
    public $bankBranch = '';

    // Company Details
    public $establishedDate = '';
    public $businessType = '';
    public $businessDescription = '';
    public $industry = '';
    public $employeeCount = '';

    // Social Media
    public $facebookUrl = '';
    public $twitterUrl = '';
    public $linkedinUrl = '';
    public $instagramUrl = '';

    // Settings
    public $financialYearStart = '04-01';
    public $currency = 'INR';
    public $timezone = 'Asia/Kolkata';

    // File uploads
    public $logo;
    public $favicon;
    public $letterhead;
    public $signature;

    // File paths
    public $currentLogoPath = '';
    public $currentFaviconPath = '';
    public $currentLetterheadPath = '';
    public $currentSignaturePath = '';

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'legalName' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'postalCode' => 'nullable|string|max:10',
            'panNumber' => 'nullable|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
            'gstin' => 'nullable|string|size:15|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
            'cin' => 'nullable|string|max:21',
            'tanNumber' => 'nullable|string|size:10',
            'fssaiNumber' => 'nullable|string|max:14',
            'msmeNumber' => 'nullable|string|max:20',
            'bankName' => 'nullable|string|max:255',
            'bankAccountNumber' => 'nullable|string|max:20',
            'bankIfscCode' => 'nullable|string|size:11|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            'bankBranch' => 'nullable|string|max:255',
            'establishedDate' => 'nullable|date',
            'businessType' => 'nullable|in:proprietorship,partnership,llp,private_limited,public_limited,other',
            'businessDescription' => 'nullable|string',
            'industry' => 'nullable|string|max:255',
            'employeeCount' => 'nullable|integer|min:1',
            'facebookUrl' => 'nullable|url',
            'twitterUrl' => 'nullable|url',
            'linkedinUrl' => 'nullable|url',
            'instagramUrl' => 'nullable|url',
            'financialYearStart' => 'required|string',
            'currency' => 'required|string|size:3',
            'timezone' => 'required|string',
            'logo' => 'nullable|image|max:2048|mimes:jpeg,png,jpg,gif,svg',
            'favicon' => 'nullable|image|max:1024|mimes:ico,png,jpg',
            'letterhead' => 'nullable|file|max:5120|mimes:pdf,jpeg,png,jpg',
            'signature' => 'nullable|image|max:1024|mimes:jpeg,png,jpg,gif',
        ];
    }

    protected $messages = [
        'name.required' => 'Company name is required',
        'panNumber.regex' => 'PAN number format is invalid (e.g., ABCDE1234F)',
        'gstin.regex' => 'GSTIN format is invalid (e.g., 22AAAAA0000A1Z5)',
        'bankIfscCode.regex' => 'IFSC code format is invalid (e.g., SBIN0001234)',
    ];

    public function mount()
    {
        $this->companyProfile = CompanyProfileModel::current();
        $this->loadCompanyData();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    private function loadCompanyData()
    {
        if ($this->companyProfile && $this->companyProfile->exists) {
            // Basic Information
            $this->name = $this->companyProfile->name ?? '';
            $this->legalName = $this->companyProfile->legal_name ?? '';
            $this->email = $this->companyProfile->email ?? '';
            $this->phone = $this->companyProfile->phone ?? '';
            $this->mobile = $this->companyProfile->mobile ?? '';
            $this->website = $this->companyProfile->website ?? '';

            // Address Information
            $this->address = $this->companyProfile->address ?? '';
            $this->city = $this->companyProfile->city ?? '';
            $this->state = $this->companyProfile->state ?? '';
            $this->country = $this->companyProfile->country ?? 'India';
            $this->postalCode = $this->companyProfile->postal_code ?? '';

            // Tax & Legal Information
            $this->panNumber = $this->companyProfile->pan_number ?? '';
            $this->gstin = $this->companyProfile->gstin ?? '';
            $this->cin = $this->companyProfile->cin ?? '';
            $this->tanNumber = $this->companyProfile->tan_number ?? '';
            $this->fssaiNumber = $this->companyProfile->fssai_number ?? '';
            $this->msmeNumber = $this->companyProfile->msme_number ?? '';

            // Banking Information
            $this->bankName = $this->companyProfile->bank_name ?? '';
            $this->bankAccountNumber = $this->companyProfile->bank_account_number ?? '';
            $this->bankIfscCode = $this->companyProfile->bank_ifsc_code ?? '';
            $this->bankBranch = $this->companyProfile->bank_branch ?? '';

            // Company Details
            $this->establishedDate = $this->companyProfile->established_date ? $this->companyProfile->established_date->format('Y-m-d') : '';
            $this->businessType = $this->companyProfile->business_type ?? '';
            $this->businessDescription = $this->companyProfile->business_description ?? '';
            $this->industry = $this->companyProfile->industry ?? '';
            $this->employeeCount = $this->companyProfile->employee_count ?? '';

            // Social Media
            $this->facebookUrl = $this->companyProfile->facebook_url ?? '';
            $this->twitterUrl = $this->companyProfile->twitter_url ?? '';
            $this->linkedinUrl = $this->companyProfile->linkedin_url ?? '';
            $this->instagramUrl = $this->companyProfile->instagram_url ?? '';

            // Settings
            $this->financialYearStart = $this->companyProfile->financial_year_start ?? '04-01';
            $this->currency = $this->companyProfile->currency ?? 'INR';
            $this->timezone = $this->companyProfile->timezone ?? 'Asia/Kolkata';

            // File paths
            $this->currentLogoPath = $this->companyProfile->logo_path ?? '';
            $this->currentFaviconPath = $this->companyProfile->favicon_path ?? '';
            $this->currentLetterheadPath = $this->companyProfile->letterhead_path ?? '';
            $this->currentSignaturePath = $this->companyProfile->signature_path ?? '';
        }
    }

    public function saveBasicInfo()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'legalName' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
        ]);

        $this->saveCompanyProfile([
            'name' => $this->name,
            'legal_name' => $this->legalName,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'website' => $this->website,
        ]);

        $this->success('Basic Information Saved!', 'Company basic information has been updated successfully.');
    }

    public function saveAddressInfo()
    {
        $this->validate([
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'postalCode' => 'nullable|string|max:10',
        ]);

        $this->saveCompanyProfile([
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postalCode,
        ]);

        $this->success('Address Information Saved!', 'Company address information has been updated successfully.');
    }

    public function saveTaxInfo()
    {
        $this->validate([
            'panNumber' => 'nullable|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
            'gstin' => 'nullable|string|size:15|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
            'cin' => 'nullable|string|max:21',
            'tanNumber' => 'nullable|string|size:10',
            'fssaiNumber' => 'nullable|string|max:14',
            'msmeNumber' => 'nullable|string|max:20',
        ]);

        $this->saveCompanyProfile([
            'pan_number' => $this->panNumber,
            'gstin' => $this->gstin,
            'cin' => $this->cin,
            'tan_number' => $this->tanNumber,
            'fssai_number' => $this->fssaiNumber,
            'msme_number' => $this->msmeNumber,
        ]);

        $this->success('Tax Information Saved!', 'Company tax and legal information has been updated successfully.');
    }

    public function saveBankingInfo()
    {
        $this->validate([
            'bankName' => 'nullable|string|max:255',
            'bankAccountNumber' => 'nullable|string|max:20',
            'bankIfscCode' => 'nullable|string|size:11|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            'bankBranch' => 'nullable|string|max:255',
        ]);

        $this->saveCompanyProfile([
            'bank_name' => $this->bankName,
            'bank_account_number' => $this->bankAccountNumber,
            'bank_ifsc_code' => $this->bankIfscCode,
            'bank_branch' => $this->bankBranch,
        ]);

        $this->success('Banking Information Saved!', 'Company banking information has been updated successfully.');
    }

    public function saveCompanyDetails()
    {
        $this->validate([
            'establishedDate' => 'nullable|date',
            'businessType' => 'nullable|in:proprietorship,partnership,llp,private_limited,public_limited,other',
            'businessDescription' => 'nullable|string',
            'industry' => 'nullable|string|max:255',
            'employeeCount' => 'nullable|integer|min:1',
        ]);

        $this->saveCompanyProfile([
            'established_date' => $this->establishedDate,
            'business_type' => $this->businessType,
            'business_description' => $this->businessDescription,
            'industry' => $this->industry,
            'employee_count' => $this->employeeCount,
        ]);

        $this->success('Company Details Saved!', 'Company details have been updated successfully.');
    }

    public function saveSocialMedia()
    {
        $this->validate([
            'facebookUrl' => 'nullable|url',
            'twitterUrl' => 'nullable|url',
            'linkedinUrl' => 'nullable|url',
            'instagramUrl' => 'nullable|url',
        ]);

        $this->saveCompanyProfile([
            'facebook_url' => $this->facebookUrl,
            'twitter_url' => $this->twitterUrl,
            'linkedin_url' => $this->linkedinUrl,
            'instagram_url' => $this->instagramUrl,
        ]);

        $this->success('Social Media Links Saved!', 'Social media information has been updated successfully.');
    }

    public function saveSettings()
    {
        $this->validate([
            'financialYearStart' => 'required|string',
            'currency' => 'required|string|size:3',
            'timezone' => 'required|string',
        ]);

        $this->saveCompanyProfile([
            'financial_year_start' => $this->financialYearStart,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
        ]);

        $this->success('Settings Saved!', 'Company settings have been updated successfully.');
    }

    public function uploadLogo()
    {
        $this->validate(['logo' => 'required|image|max:2048|mimes:jpeg,png,jpg,gif,svg']);

        $this->uploadFile('logo', 'company/logos');
    }

    public function uploadFavicon()
    {
        $this->validate(['favicon' => 'required|image|max:1024|mimes:ico,png,jpg']);

        $this->uploadFile('favicon', 'company/favicons');
    }

    public function uploadLetterhead()
    {
        $this->validate(['letterhead' => 'required|file|max:5120|mimes:pdf,jpeg,png,jpg']);

        $this->uploadFile('letterhead', 'company/letterheads');
    }

    public function uploadSignature()
    {
        $this->validate(['signature' => 'required|image|max:1024|mimes:jpeg,png,jpg,gif']);

        $this->uploadFile('signature', 'company/signatures');
    }

    private function uploadFile($fileProperty, $directory)
    {
        try {
            $file = $this->$fileProperty;

            if ($file) {
                // Delete old file if exists
                $currentPathProperty = 'current' . ucfirst($fileProperty) . 'Path';
                if ($this->$currentPathProperty && Storage::exists($this->$currentPathProperty)) {
                    Storage::delete($this->$currentPathProperty);
                }

                // Store new file
                $path = $file->store($directory, 'public');

                // Update database
                $this->saveCompanyProfile([$fileProperty . '_path' => $path]);

                // Update current path
                $this->$currentPathProperty = $path;

                // Reset file input
                $this->$fileProperty = null;

                $this->success(ucfirst($fileProperty) . ' Uploaded!', ucfirst($fileProperty) . ' has been uploaded successfully.');
            }
        } catch (\Exception $e) {
            Log::error('Error uploading ' . $fileProperty . ': ' . $e->getMessage());
            $this->error('Upload Failed!', 'Error uploading ' . $fileProperty . ': ' . $e->getMessage());
        }
    }

    public function removeFile($fileType)
    {
        try {
            $pathProperty = $fileType . '_path';
            $currentPathProperty = 'current' . ucfirst($fileType) . 'Path';

            if ($this->$currentPathProperty && Storage::exists($this->$currentPathProperty)) {
                Storage::delete($this->$currentPathProperty);
            }

            $this->saveCompanyProfile([$pathProperty => null]);
            $this->$currentPathProperty = '';

            $this->success('File Removed!', ucfirst($fileType) . ' has been removed successfully.');
        } catch (\Exception $e) {
            Log::error('Error removing ' . $fileType . ': ' . $e->getMessage());
            $this->error('Remove Failed!', 'Error removing ' . $fileType);
        }
    }

    private function saveCompanyProfile($data)
    {
        try {
            if ($this->companyProfile && $this->companyProfile->exists) {
                $this->companyProfile->update($data);
            } else {
                $this->companyProfile = CompanyProfileModel::create(array_merge($data, ['is_active' => true]));
            }
        } catch (\Exception $e) {
            Log::error('Error saving company profile: ' . $e->getMessage());
            throw $e;
        }
    }

    public function render()
    {
        return view('livewire.company-profile');
    }
}
