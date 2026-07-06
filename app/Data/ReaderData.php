<?php

namespace App\Data;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * DTO for passing data from Controller → Service.
 * A typed object instead of an array (`$data['x']`).
 */
class ReaderData
{
    public function __construct(
        public readonly string $full_name,
        public readonly string $type,
        public readonly string $status,
        public readonly ?string $id_number,
        public readonly ?string $registration_number,
        public readonly ?string $affiliation_place,
        public readonly ?string $affiliation_unit,
        public readonly ?string $affiliation_group,
        public readonly ?string $nationality,
        public readonly ?string $birth_date,
        public readonly ?string $passport,
        public readonly ?string $pinfl,
        public readonly ?string $gender,
        public readonly ?string $district,
        public readonly ?string $address,
        public readonly ?string $phone,
        public readonly ?int $member_year,
        public readonly ?string $issued_date,
        public readonly ?string $other_library_member,
        public readonly ?string $note,
        public readonly ?UploadedFile $photo,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            full_name: $request->string('full_name')->toString(),
            type: $request->string('type')->toString(),
            status: $request->string('status')->toString(),
            id_number: $request->input('id_number'),
            registration_number: $request->input('registration_number'),
            affiliation_place: $request->input('affiliation_place'),
            affiliation_unit: $request->input('affiliation_unit'),
            affiliation_group: $request->input('affiliation_group'),
            nationality: $request->input('nationality'),
            birth_date: $request->input('birth_date') ?: null,
            passport: $request->input('passport'),
            pinfl: $request->input('pinfl'),
            gender: $request->input('gender') ?: null,
            district: $request->input('district'),
            address: $request->input('address'),
            phone: $request->input('phone'),
            member_year: $request->integer('member_year') ?: null,
            issued_date: $request->input('issued_date') ?: null,
            other_library_member: $request->input('other_library_member'),
            note: $request->input('note'),
            photo: $request->file('photo'),
        );
    }

    /**
     * Only the scalar fields written to the readers table (without files/relationships).
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'full_name' => $this->full_name,
            'type' => $this->type,
            'status' => $this->status,
            'id_number' => $this->id_number,
            'registration_number' => $this->registration_number,
            'affiliation_place' => $this->affiliation_place,
            'affiliation_unit' => $this->affiliation_unit,
            'affiliation_group' => $this->affiliation_group,
            'nationality' => $this->nationality,
            'birth_date' => $this->birth_date,
            'passport' => $this->passport,
            'pinfl' => $this->pinfl,
            'gender' => $this->gender,
            'district' => $this->district,
            'address' => $this->address,
            'phone' => $this->phone,
            'member_year' => $this->member_year,
            'issued_date' => $this->issued_date,
            'other_library_member' => $this->other_library_member,
            'note' => $this->note,
        ];
    }
}
