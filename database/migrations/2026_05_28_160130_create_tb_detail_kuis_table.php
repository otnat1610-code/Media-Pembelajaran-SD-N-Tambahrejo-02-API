import { useEffect, useState } from "react";
import axiosInstance from "../../config/axios";
import { toast } from "sonner";

import {
  Plus,
  Pencil,
  Trash2,
  Save,
  X,
  BookOpen,
  Eye,
  ImagePlus,
} from "lucide-react";

import PageHeader from "../../Components/admin/PageHeader.jsx";
import Button from "../../Components/admin/Button.jsx";
import PengaturanNilai from "./PengaturanNilai";

export default function Kuis() {
  // =========================
  // DATA KUIS
  // =========================
  const [dataKuis, setDataKuis] = useState([]);

  // =========================
  // MODAL
  // =========================
  const [showTambahModal, setShowTambahModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showDetailModal, setShowDetailModal] = useState(false);

  const [detailKuis, setDetailKuis] = useState(null);

  const [showPengaturan, setShowPengaturan] = useState(false);
  const [jumlahSoal, setJumlahSoal] = useState("");
  const [editJumlahSoal, setEditJumlahSoal] = useState("");

  // =========================
  // FORM KUIS
  // =========================
  const [form, setForm] = useState({
    judul: "",
    deskripsi: "",
    status: "aktif",
  });

  // =========================
  // FORM EDIT
  // =========================
  const [editForm, setEditForm] = useState({
    id_kuis: null,
    judul: "",
    deskripsi: "",
    status: "aktif",
  });

  // =========================
  // DEFAULT SOAL
  // =========================
  const defaultSoal = {
    pertanyaan: "",
    gambar_pertanyaan: null,

    pilihan: {
      A: "",
      B: "",
      C: "",
      D: "",
    },

    gambar_pilihan: {
      A: null,
      B: null,
      C: null,
      D: null,
    },

    jawaban: "A",
  };

  // =========================
  // SOAL TAMBAH
  // =========================
  const [soalList, setSoalList] = useState(
    Array.from({ length: 5 }, () => ({
      ...defaultSoal,
      pilihan: { ...defaultSoal.pilihan },
      gambar_pilihan: {
        ...defaultSoal.gambar_pilihan,
      },
    }))
  );

  // =========================
  // SOAL EDIT
  // =========================
  const [editSoalList, setEditSoalList] = useState([]);

  // =========================
  // FETCH DATA
  // =========================
  useEffect(() => {
    fetchKuis();
  }, []);

  const fetchKuis = async () => {
    try {
      const response = await axiosInstance.get("/admin/kuis");
      setDataKuis(response.data);
    } catch (error) {
      console.log(error);
      toast.error("Gagal mengambil data kuis.");
    }
  };

  // =========================
  // HANDLE FORM
  // =========================
  const handleFormChange = (e) => {
    setForm({
      ...form,
      [e.target.name]: e.target.value,
    });
  };

  // =========================
  // HANDLE EDIT FORM
  // =========================
  const handleEditChange = (e) => {
    setEditForm({
      ...editForm,
      [e.target.name]: e.target.value,
    });
  };

  // =========================
  // HANDLE SOAL
  // =========================
  const handleSoalChange = (index, field, value) => {
    const updated = [...soalList];

    updated[index] = {
      ...updated[index],
      [field]: value,
    };

    setSoalList(updated);
  };

  // =========================
  // HANDLE PILIHAN
  // =========================
  const handlePilihanChange = (index, option, value) => {
    const updated = [...soalList];

    updated[index] = {
      ...updated[index],
      pilihan: {
        ...updated[index].pilihan,
        [option]: value,
      },
    };

    setSoalList(updated);
  };

  // =========================
  // HANDLE GAMBAR PERTANYAAN
  // =========================
  const handleGambarPertanyaanChange = (index, file) => {
    if (!file) return;

    if (!file.type.startsWith("image/")) {
      toast.error("File harus berupa gambar.");
      return;
    }

    const maxSize = 5 * 1024 * 1024;

    if (file.size > maxSize) {
      toast.error("Ukuran gambar maksimal 5 MB.");
      return;
    }

    const updated = [...soalList];

    updated[index] = {
      ...updated[index],
      gambar_pertanyaan: file,
    };

    setSoalList(updated);
  };

  // =========================
  // HANDLE GAMBAR PILIHAN
  // =========================
  const handleGambarPilihanChange = (
    index,
    option,
    file
  ) => {
    if (!file) return;

    if (!file.type.startsWith("image/")) {
      toast.error("File harus berupa gambar.");
      return;
    }

    const maxSize = 5 * 1024 * 1024;

    if (file.size > maxSize) {
      toast.error("Ukuran gambar maksimal 5 MB.");
      return;
    }

    const updated = [...soalList];

    updated[index] = {
      ...updated[index],
      gambar_pilihan: {
        ...updated[index].gambar_pilihan,
        [option]: file,
      },
    };

    setSoalList(updated);
  };

  // =========================
  // HAPUS GAMBAR PERTANYAAN
  // =========================
  const hapusGambarPertanyaan = (index) => {
    const updated = [...soalList];

    updated[index] = {
      ...updated[index],
      gambar_pertanyaan: null,
    };

    setSoalList(updated);
  };

  // =========================
  // HAPUS GAMBAR PILIHAN
  // =========================
  const hapusGambarPilihan = (index, option) => {
    const updated = [...soalList];

    updated[index] = {
      ...updated[index],
      gambar_pilihan: {
        ...updated[index].gambar_pilihan,
        [option]: null,
      },
    };

    setSoalList(updated);
  };

  // =========================
  // HANDLE EDIT SOAL
  // =========================
  const handleEditSoalChange = (
    index,
    field,
    value
  ) => {
    const updated = [...editSoalList];

    updated[index] = {
      ...updated[index],
      [field]: value,
    };

    setEditSoalList(updated);
  };

  // =========================
  // HANDLE EDIT PILIHAN
  // =========================
  const handleEditPilihanChange = (
    index,
    option,
    value
  ) => {
    const updated = [...editSoalList];

    updated[index] = {
      ...updated[index],
      pilihan: {
        ...updated[index].pilihan,
        [option]: value,
      },
    };

    setEditSoalList(updated);
  };

  // =========================
  // HANDLE EDIT GAMBAR PERTANYAAN
  // =========================
  const handleEditGambarPertanyaanChange = (
    index,
    file
  ) => {
    if (!file) return;

    if (!file.type.startsWith("image/")) {
      toast.error("File harus berupa gambar.");
      return;
    }

    const maxSize = 5 * 1024 * 1024;

    if (file.size > maxSize) {
      toast.error("Ukuran gambar maksimal 5 MB.");
      return;
    }

    const updated = [...editSoalList];

    updated[index] = {
      ...updated[index],
      gambar_pertanyaan: file,
    };

    setEditSoalList(updated);
  };

  // =========================
  // HANDLE EDIT GAMBAR PILIHAN
  // =========================
  const handleEditGambarPilihanChange = (
    index,
    option,
    file
  ) => {
    if (!file) return;

    if (!file.type.startsWith("image/")) {
      toast.error("File harus berupa gambar.");
      return;
    }

    const maxSize = 5 * 1024 * 1024;

    if (file.size > maxSize) {
      toast.error("Ukuran gambar maksimal 5 MB.");
      return;
    }

    const updated = [...editSoalList];

    updated[index] = {
      ...updated[index],
      gambar_pilihan: {
        ...updated[index].gambar_pilihan,
        [option]: file,
      },
    };

    setEditSoalList(updated);
  };

  // =========================
  // HAPUS GAMBAR EDIT
  // =========================
  const hapusEditGambarPertanyaan = (index) => {
    const updated = [...editSoalList];

    updated[index] = {
      ...updated[index],
      gambar_pertanyaan: null,
      hapus_gambar_pertanyaan: true,
    };

    setEditSoalList(updated);
  };

  const hapusEditGambarPilihan = (
    index,
    option
  ) => {
    const updated = [...editSoalList];

    updated[index] = {
      ...updated[index],
      gambar_pilihan: {
        ...updated[index].gambar_pilihan,
        [option]: null,
      },

      hapus_gambar_pilihan: {
        ...(updated[index]
          .hapus_gambar_pilihan || {}),
        [option]: true,
      },
    };

    setEditSoalList(updated);
  };

  // =========================
  // TAMBAH SOAL
  // =========================
  const tambahSoal = () => {
    setSoalList([
      ...soalList,
      {
        ...defaultSoal,
        pilihan: {
          ...defaultSoal.pilihan,
        },
        gambar_pilihan: {
          ...defaultSoal.gambar_pilihan,
        },
      },
    ]);
  };

  // =========================
  // TAMBAH EDIT SOAL
  // =========================
  const tambahEditSoal = () => {
    setEditSoalList([
      ...editSoalList,
      {
        ...defaultSoal,
        pilihan: {
          ...defaultSoal.pilihan,
        },
        gambar_pilihan: {
          ...defaultSoal.gambar_pilihan,
        },
      },
    ]);
  };

  // =========================
  // HAPUS SOAL
  // =========================
  const hapusSoal = (index) => {
    if (soalList.length <= 5) {
      toast.error("Minimal harus terdapat 5 soal.");
      return;
    }

    const updated = soalList.filter(
      (_, i) => i !== index
    );

    setSoalList(updated);
  };

  // =========================
  // HAPUS EDIT SOAL
  // =========================
  const hapusEditSoal = (index) => {
    if (editSoalList.length <= 5) {
      toast.error("Minimal harus terdapat 5 soal.");
      return;
    }

    const updated = editSoalList.filter(
      (_, i) => i !== index
    );

    setEditSoalList(updated);
  };

  // =========================
  // BUAT FORMDATA
  // =========================
  const createKuisFormData = (
    kuisForm,
    listSoal
  ) => {
    const formData = new FormData();

    formData.append("judul", kuisForm.judul);
    formData.append(
      "deskripsi",
      kuisForm.deskripsi || ""
    );
    formData.append("status", kuisForm.status);
    formData.append(
      "total_soal",
      listSoal.length
    );

    listSoal.forEach((soal, index) => {
      formData.append(
        `soal[${index}][pertanyaan]`,
        soal.pertanyaan
      );

      formData.append(
        `soal[${index}][jawaban]`,
        soal.jawaban
      );

      formData.append(
        `soal[${index}][pilihan][A]`,
        soal.pilihan.A
      );

      formData.append(
        `soal[${index}][pilihan][B]`,
        soal.pilihan.B
      );

      formData.append(
        `soal[${index}][pilihan][C]`,
        soal.pilihan.C
      );

      formData.append(
        `soal[${index}][pilihan][D]`,
        soal.pilihan.D
      );

      // =========================
      // GAMBAR PERTANYAAN
      // =========================
      if (
        soal.gambar_pertanyaan instanceof File
      ) {
        formData.append(
          `soal[${index}][gambar_pertanyaan]`,
          soal.gambar_pertanyaan
        );
      }

      // =========================
      // GAMBAR PILIHAN
      // =========================
      ["A", "B", "C", "D"].forEach(
        (option) => {
          if (
            soal.gambar_pilihan?.[option] instanceof
            File
          ) {
            formData.append(
              `soal[${index}][gambar_pilihan][${option}]`,
              soal.gambar_pilihan[option]
            );
          }
        }
      );

      // =========================
      // HAPUS GAMBAR SAAT EDIT
      // =========================
      if (soal.hapus_gambar_pertanyaan) {
        formData.append(
          `soal[${index}][hapus_gambar_pertanyaan]`,
          "1"
        );
      }

      if (soal.hapus_gambar_pilihan) {
        ["A", "B", "C", "D"].forEach(
          (option) => {
            if (
              soal.hapus_gambar_pilihan[
                option
              ]
            ) {
              formData.append(
                `soal[${index}][hapus_gambar_pilihan][${option}]`,
                "1"
              );
            }
          }
        );
      }
    });

    return formData;
  };

  // =========================
  // TAMBAH KUIS
  // =========================
  const handleTambah = async (e) => {
    e.preventDefault();

    if (soalList.length < 5) {
      toast.error("Jumlah soal minimal 5.");
      return;
    }

    if (!form.judul.trim()) {
      toast.error("Judul kuis tidak boleh kosong.");
      return;
    }

    const soalKosong = soalList.find(
      (s) =>
        !s.pertanyaan.trim() ||
        !s.pilihan.A.trim() ||
        !s.pilihan.B.trim() ||
        !s.pilihan.C.trim() ||
        !s.pilihan.D.trim()
    );

    if (soalKosong) {
      toast.error(
        "Semua soal dan pilihan jawaban harus diisi."
      );
      return;
    }

    try {
      const formData = createKuisFormData(
        form,
        soalList
      );

      await axiosInstance.post(
        "/kuis",
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        }
      );

      await fetchKuis();

      setForm({
        judul: "",
        deskripsi: "",
        status: "aktif",
      });

      setSoalList(
        Array.from({ length: 5 }, () => ({
          ...defaultSoal,
          pilihan: {
            ...defaultSoal.pilihan,
          },
          gambar_pilihan: {
            ...defaultSoal.gambar_pilihan,
          },
        }))
      );

      setShowTambahModal(false);

      toast.success(
        "Kuis berhasil ditambahkan."
      );
    } catch (error) {
      console.log(error);
      console.log(error.response?.data);

      toast.error(
        error.response?.data?.message ||
          "Gagal menambahkan kuis."
      );
    }
  };

  // =========================
  // DETAIL
  // =========================
  const handleOpenDetail = (kuis) => {
    setDetailKuis(kuis);
    setShowDetailModal(true);
  };

  // =========================
  // OPEN EDIT
  // =========================
  const handleOpenEdit = (kuis) => {
    setEditForm({
      id_kuis: kuis.id_kuis,
      judul: kuis.judul,
      deskripsi: kuis.deskripsi || "",
      status: kuis.status,
    });

    const formattedSoal =
      kuis.detail_kuis.map((item) => ({
        id_detail_kuis:
          item.id_detail_kuis,

        pertanyaan:
          item.pertanyaan || "",

        gambar_pertanyaan:
          item.gambar_pertanyaan || null,

        pilihan: {
          A: item.pilihan_a || "",
          B: item.pilihan_b || "",
          C: item.pilihan_c || "",
          D: item.pilihan_d || "",
        },

        gambar_pilihan: {
          A:
            item.gambar_pilihan_a ||
            null,
          B:
            item.gambar_pilihan_b ||
            null,
          C:
            item.gambar_pilihan_c ||
            null,
          D:
            item.gambar_pilihan_d ||
            null,
        },

        jawaban: item.jawaban,

        hapus_gambar_pertanyaan: false,

        hapus_gambar_pilihan: {},
      }));

    setEditSoalList(formattedSoal);

    setShowEditModal(true);
  };

  // =========================
  // UPDATE KUIS
  // =========================
  const handleUpdate = async (e) => {
    e.preventDefault();

    if (editSoalList.length < 5) {
      toast.error("Jumlah soal minimal 5.");
      return;
    }

    if (!editForm.judul.trim()) {
      toast.error(
        "Judul kuis tidak boleh kosong."
      );
      return;
    }

    const soalKosong = editSoalList.find(
      (s) =>
        !s.pertanyaan.trim() ||
        !s.pilihan.A.trim() ||
        !s.pilihan.B.trim() ||
        !s.pilihan.C.trim() ||
        !s.pilihan.D.trim()
    );

    if (soalKosong) {
      toast.error(
        "Semua soal dan pilihan jawaban harus diisi."
      );
      return;
    }

    try {
      const formData = createKuisFormData(
        editForm,
        editSoalList
      );

      // Laravel PUT dengan multipart terkadang
      // bermasalah, sehingga gunakan POST + _method
      formData.append("_method", "PUT");

      await axiosInstance.post(
        `/kuis/${editForm.id_kuis}`,
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        }
      );

      await fetchKuis();

      setShowEditModal(false);

      toast.success(
        "Kuis berhasil diperbarui."
      );
    } catch (error) {
      console.log(error);
      console.log(error.response?.data);

      if (error.response?.data?.errors) {
        const firstError = Object.values(
          error.response.data.errors
        )[0]?.[0];

        toast.error(
          firstError ||
            "Terdapat kesalahan validasi."
        );

        return;
      }

      toast.error(
        error.response?.data?.message ||
          "Gagal update kuis."
      );
    }
  };

  // =========================
  // KOMPONEN INPUT GAMBAR
  // =========================
  const ImageInput = ({
    label,
    value,
    onChange,
    onRemove,
  }) => {
    const preview =
      value instanceof File
        ? URL.createObjectURL(value)
        : value;

    return (
      <div className="mt-3">
        <label className="block mb-2 text-sm font-medium text-slate-700">
          {label}{" "}
          <span className="text-slate-400 font-normal">
            (Opsional)
          </span>
        </label>

        <div className="flex flex-wrap items-start gap-3">
          <label className="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-medium text-blue-700 transition hover:bg-blue-100">
            <ImagePlus className="h-4 w-4" />

            Pilih Gambar

            <input
              type="file"
              accept="image/png,image/jpeg,image/jpg,image/webp"
              className="hidden"
              onChange={(e) =>
                onChange(
                  e.target.files?.[0] || null
                )
              }
            />
          </label>

          {value && (
            <button
              type="button"
              onClick={onRemove}
              className="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-100"
            >
              <X className="h-4 w-4" />
              Hapus Gambar
            </button>
          )}
        </div>

        {preview && (
          <div className="relative mt-3 w-fit">
            <img
              src={preview}
              alt="Preview"
              className="max-h-48 max-w-xs rounded-xl border border-slate-200 object-contain shadow-sm"
            />
          </div>
        )}

        <p className="mt-1 text-xs text-slate-400">
          JPG, JPEG, PNG atau WEBP. Maksimal 5 MB.
        </p>
      </div>
    );
  };

  // =========================
  // RENDER
  // =========================
  return (
    <div>
      {/* =========================
          HEADER
      ========================= */}
      <PageHeader
        title="Manajemen Kuis"
        description="Daftar kuis pembelajaran."
        actions={
          <div className="flex gap-2">
            <Button
              onClick={() =>
                setShowPengaturan(true)
              }
              className="bg-slate-500 hover:bg-slate-600 text-white"
            >
              Pengaturan Jumlah Soal
            </Button>

            <Button
              onClick={() =>
                setShowTambahModal(true)
              }
              className="bg-blue-600 hover:bg-blue-700 text-white"
            >
              <Plus className="h-4 w-4" />
              Tambah Kuis
            </Button>
          </div>
        }
      />

      {/* =========================
          TABLE
      ========================= */}
      <div className="bg-white rounded-xl shadow-card border border-slate-100 overflow-x-auto">
        <table className="w-full text-sm">
          <thead className="bg-slate-50 text-slate-600">
            <tr>
              <th className="px-5 py-3 text-left font-semibold">
                Judul
              </th>

              <th className="px-5 py-3 text-left font-semibold">
                Deskripsi
              </th>

              <th className="px-5 py-3 text-left font-semibold">
                Total Soal
              </th>

              <th className="px-5 py-3 text-left font-semibold">
                Status
              </th>

              <th className="px-5 py-3 text-left font-semibold">
                Aksi
              </th>
            </tr>
          </thead>

          <tbody>
            {dataKuis.map((k) => (
              <tr
                key={k.id_kuis}
                className="border-t border-slate-100 hover:bg-slate-50"
              >
                <td className="px-5 py-3 font-medium">
                  {k.judul}
                </td>

                <td className="px-5 py-3 text-slate-600">
                  {k.deskripsi}
                </td>

                <td className="px-5 py-3">
                  <div className="inline-flex items-center gap-2 rounded-full bg-blue-100 text-blue-700 px-3 py-1 text-xs font-semibold">
                    <BookOpen className="h-4 w-4" />
                    {k.total_soal} Soal
                  </div>
                </td>

                <td className="px-5 py-3">
                  <div
                    className={`inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold ${
                      k.status === "aktif"
                        ? "bg-green-100 text-green-700"
                        : "bg-yellow-100 text-yellow-700"
                    }`}
                  >
                    {k.status === "aktif"
                      ? "Aktif"
                      : "Draft"}
                  </div>
                </td>

                <td className="px-5 py-3 flex gap-2">
                  <Button
                    variant="outline"
                    onClick={() =>
                      handleOpenEdit(k)
                    }
                  >
                    <Pencil className="h-4 w-4" />
                  </Button>

                  <Button
                    variant="outline"
                    onClick={() =>
                      handleOpenDetail(k)
                    }
                  >
                    <Eye className="h-4 w-4" />
                  </Button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* =====================================================
          MODAL TAMBAH KUIS
      ===================================================== */}
      {showTambahModal && (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-black/40 p-4">
          <div className="mx-auto w-full max-w-5xl rounded-2xl bg-white shadow-xl">
            {/* HEADER */}
            <div className="flex items-center justify-between border-b px-6 py-4">
              <div>
                <h2 className="text-xl font-bold text-slate-800">
                  Tambah Kuis
                </h2>

                <p className="text-sm text-slate-500">
                  Tambahkan kuis beserta soal.
                </p>
              </div>

              <button
                type="button"
                onClick={() =>
                  setShowTambahModal(false)
                }
                className="rounded-lg p-2 hover:bg-slate-100"
              >
                <X className="h-5 w-5 text-slate-500" />
              </button>
            </div>

            {/* FORM */}
            <form
              onSubmit={handleTambah}
              className="space-y-8 p-6"
            >
              {/* DATA KUIS */}
              <div className="space-y-4">
                <div>
                  <label className="block mb-1 text-sm font-medium">
                    Judul Kuis
                  </label>

                  <input
                    type="text"
                    name="judul"
                    value={form.judul}
                    onChange={handleFormChange}
                    placeholder="Masukkan judul kuis"
                    className="w-full border border-slate-300 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>

                <div>
                  <label className="block mb-1 text-sm font-medium">
                    Deskripsi
                  </label>

                  <textarea
                    name="deskripsi"
                    value={form.deskripsi}
                    onChange={handleFormChange}
                    rows="3"
                    placeholder="Masukkan deskripsi kuis"
                    className="w-full border border-slate-300 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>

                <div>
                  <label className="block mb-2 text-sm font-medium">
                    Status
                  </label>

                  <Button
                    type="button"
                    onClick={() =>
                      setForm({
                        ...form,
                        status:
                          form.status === "aktif"
                            ? "draft"
                            : "aktif",
                      })
                    }
                    className={`min-w-[180px] py-3 justify-center font-semibold border transition-all duration-200 ${
                      form.status === "aktif"
                        ? "border-emerald-300 bg-emerald-100 !text-emerald-800 hover:bg-emerald-200"
                        : "border-orange-300 bg-orange-100 !text-orange-800 hover:bg-orange-200"
                    }`}
                  >
                    {form.status === "aktif"
                      ? "Aktif"
                      : "Draft"}
                  </Button>
                </div>
              </div>

              {/* SOAL */}
              <div className="space-y-6">
                {soalList.map(
                  (soal, index) => (
                    <div
                      key={index}
                      className="border border-slate-200 rounded-2xl p-5 bg-slate-50"
                    >
                      <div className="flex items-center justify-between mb-4">
                        <h2 className="font-bold text-lg">
                          Soal {index + 1}
                        </h2>

                        {soalList.length > 5 && (
                          <button
                            type="button"
                            onClick={() =>
                              hapusSoal(index)
                            }
                            className="text-red-500 hover:text-red-700"
                          >
                            <Trash2 className="h-5 w-5" />
                          </button>
                        )}
                      </div>

                      {/* PERTANYAAN */}
                      <div className="mb-4">
                        <label className="block mb-1 text-sm font-medium">
                          Pertanyaan
                        </label>

                        <textarea
                          value={
                            soal.pertanyaan
                          }
                          onChange={(e) =>
                            handleSoalChange(
                              index,
                              "pertanyaan",
                              e.target.value
                            )
                          }
                          rows="3"
                          placeholder="Masukkan pertanyaan"
                          className="w-full border border-slate-300 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500"
                        />

                        <ImageInput
                          label="Gambar Pertanyaan"
                          value={
                            soal.gambar_pertanyaan
                          }
                          onChange={(file) =>
                            handleGambarPertanyaanChange(
                              index,
                              file
                            )
                          }
                          onRemove={() =>
                            hapusGambarPertanyaan(
                              index
                            )
                          }
                        />
                      </div>

                      {/* PILIHAN */}
                      <div className="grid md:grid-cols-2 gap-4">
                        {[
                          "A",
                          "B",
                          "C",
                          "D",
                        ].map((option) => (
                          <div
                            key={option}
                            className="rounded-xl border border-slate-200 bg-white p-4"
                          >
                            <label className="block mb-1 text-sm font-medium">
                              Pilihan {option}
                            </label>

                            <input
                              type="text"
                              value={
                                soal.pilihan[
                                  option
                                ]
                              }
                              onChange={(e) =>
                                handlePilihanChange(
                                  index,
                                  option,
                                  e.target.value
                                )
                              }
                              placeholder={`Masukkan pilihan ${option}`}
                              className="w-full border border-slate-300 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500"
                            />

                            <ImageInput
                              label={`Gambar Pilihan ${option}`}
                              value={
                                soal
                                  .gambar_pilihan[
                                  option
                                ]
                              }
                              onChange={(file) =>
                                handleGambarPilihanChange(
                                  index,
                                  option,
                                  file
                                )
                              }
                              onRemove={() =>
                                hapusGambarPilihan(
                                  index,
                                  option
                                )
                              }
                            />
                          </div>
                        ))}
                      </div>

                      {/* JAWABAN */}
                      <div className="mt-4">
                        <label className="block mb-1 text-sm font-medium">
                          Jawaban Benar
                        </label>

                        <select
                          value={
                            soal.jawaban
                          }
                          onChange={(e) =>
                            handleSoalChange(
                              index,
                              "jawaban",
                              e.target.value
                            )
                          }
                          className="w-full border border-slate-300 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500"
                        >
                          <option value="A">
                            Pilihan A
                          </option>

                          <option value="B">
                            Pilihan B
                          </option>

                          <option value="C">
                            Pilihan C
                          </option>

                          <option value="D">
                            Pilihan D
                          </option>
                        </select>
                      </div>
                    </div>
                  )
                )}
              </div>

              {/* BUTTON */}
              <div className="flex flex-wrap gap-3">
                <button
                  type="button"
                  onClick={tambahSoal}
                  className="flex items-center gap-2 px-5 py-3 rounded-xl bg-blue-100 text-blue-700 hover:bg-blue-200"
                >
                  <Plus className="h-5 w-5" />
                  Tambah Soal
                </button>

                <button
                  type="submit"
                  className="flex items-center gap-2 bg-blue-600 text-white px-5 py-3 rounded-xl hover:bg-blue-700"
                >
                  <Save className="h-5 w-5" />
                  Simpan Kuis
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* =====================================================
          MODAL EDIT
      ===================================================== */}
      {showEditModal && (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-black/40 p-4">
          <div className="mx-auto w-full max-w-5xl rounded-2xl bg-white shadow-xl">
            {/* HEADER */}
            <div className="flex items-center justify-between border-b px-6 py-4">
              <div>
                <h2 className="text-lg font-bold text-slate-800">
                  Edit Kuis
                </h2>

                <p className="text-sm text-slate-500">
                  Ubah data kuis.
                </p>
              </div>

              <button
                type="button"
                onClick={() =>
                  setShowEditModal(false)
                }
                className="rounded-lg p-2 hover:bg-slate-100"
              >
                <X className="h-5 w-5 text-slate-500" />
              </button>
            </div>

            <form
              onSubmit={handleUpdate}
              className="space-y-6 p-6"
            >
              {/* JUDUL */}
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">
                  Judul
                </label>

                <input
                  type="text"
                  name="judul"
                  value={editForm.judul}
                  onChange={handleEditChange}
                  className="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
              </div>

              {/* DESKRIPSI */}
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">
                  Deskripsi
                </label>

                <textarea
                  name="deskripsi"
                  value={
                    editForm.deskripsi
                  }
                  onChange={
                    handleEditChange
                  }
                  rows="3"
                  className="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
              </div>

              {/* STATUS */}
              <div>
                <label className="block mb-2 text-sm font-medium">
                  Status
                </label>

                <button
                  type="button"
                  onClick={() =>
                    setEditForm({
                      ...editForm,
                      status:
                        editForm.status ===
                        "aktif"
                          ? "draft"
                          : "aktif",
                    })
                  }
                  className={`min-w-[180px] rounded-xl border px-4 py-3 flex items-center justify-center font-semibold transition-all duration-200 ${
                    editForm.status === "aktif"
                      ? "border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100"
                      : "border-orange-300 bg-orange-50 text-orange-700 hover:bg-orange-100"
                  }`}
                >
                  {editForm.status === "aktif"
                    ? "Aktif"
                    : "Draft"}
                </button>
              </div>

              {/* EDIT SOAL */}
              <div className="space-y-6">
                {editSoalList.map(
                  (soal, index) => (
                    <div
                      key={
                        soal.id_detail_kuis ||
                        index
                      }
                      className="border border-slate-200 rounded-2xl p-5 bg-slate-50"
                    >
                      <div className="flex items-center justify-between mb-4">
                        <h2 className="font-bold text-lg">
                          Soal {index + 1}
                        </h2>

                        {editSoalList.length >
                          5 && (
                          <button
                            type="button"
                            onClick={() =>
                              hapusEditSoal(
                                index
                              )
                            }
                            className="text-red-500 hover:text-red-700"
                          >
                            <Trash2 className="h-5 w-5" />
                          </button>
                        )}
                      </div>

                      {/* PERTANYAAN */}
                      <div className="mb-4">
                        <label className="block mb-1 text-sm font-medium">
                          Pertanyaan
                        </label>

                        <textarea
                          value={
                            soal.pertanyaan
                          }
                          onChange={(e) =>
                            handleEditSoalChange(
                              index,
                              "pertanyaan",
                              e.target.value
                            )
                          }
                          rows="3"
                          className="w-full border border-slate-300 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500"
                        />

                        <ImageInput
                          label="Gambar Pertanyaan"
                          value={
                            soal.gambar_pertanyaan
                          }
                          onChange={(file) =>
                            handleEditGambarPertanyaanChange(
                              index,
                              file
                            )
                          }
                          onRemove={() =>
                            hapusEditGambarPertanyaan(
                              index
                            )
                          }
                        />
                      </div>

                      {/* PILIHAN */}
                      <div className="grid md:grid-cols-2 gap-4">
                        {[
                          "A",
                          "B",
                          "C",
                          "D",
                        ].map((option) => (
                          <div
                            key={option}
                            className="rounded-xl border border-slate-200 bg-white p-4"
                          >
                            <label className="block mb-1 text-sm font-medium">
                              Pilihan {option}
                            </label>

                            <input
                              type="text"
                              value={
                                soal.pilihan[
                                  option
                                ]
                              }
                              onChange={(e) =>
                                handleEditPilihanChange(
                                  index,
                                  option,
                                  e.target.value
                                )
                              }
                              className="w-full border border-slate-300 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500"
                            />

                            <ImageInput
                              label={`Gambar Pilihan ${option}`}
                              value={
                                soal
                                  .gambar_pilihan[
                                  option
                                ]
                              }
                              onChange={(file) =>
                                handleEditGambarPilihanChange(
                                  index,
                                  option,
                                  file
                                )
                              }
                              onRemove={() =>
                                hapusEditGambarPilihan(
                                  index,
                                  option
                                )
                              }
                            />
                          </div>
                        ))}
                      </div>

                      {/* JAWABAN */}
                      <div className="mt-4">
                        <label className="block mb-1 text-sm font-medium">
                          Jawaban Benar
                        </label>

                        <select
                          value={
                            soal.jawaban
                          }
                          onChange={(e) =>
                            handleEditSoalChange(
                              index,
                              "jawaban",
                              e.target.value
                            )
                          }
                          className="w-full border border-slate-300 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500"
                        >
                          <option value="A">
                            Pilihan A
                          </option>

                          <option value="B">
                            Pilihan B
                          </option>

                          <option value="C">
                            Pilihan C
                          </option>

                          <option value="D">
                            Pilihan D
                          </option>
                        </select>
                      </div>
                    </div>
                  )
                )}

                <button
                  type="button"
                  onClick={tambahEditSoal}
                  className="flex items-center gap-2 px-5 py-3 rounded-xl bg-blue-100 text-blue-700 hover:bg-blue-200"
                >
                  <Plus className="h-5 w-5" />
                  Tambah Soal
                </button>
              </div>

              {/* BUTTON */}
              <div className="flex justify-end gap-3 pt-2">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() =>
                    setShowEditModal(false)
                  }
                >
                  Batal
                </Button>

                <Button
                  type="submit"
                  className="bg-yellow-500 hover:bg-yellow-600 text-white"
                >
                  Update
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* =====================================================
          MODAL DETAIL
      ===================================================== */}
      {showDetailModal && detailKuis && (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-black/40 p-4">
          <div className="mx-auto w-full max-w-5xl rounded-2xl bg-white shadow-xl">
            {/* HEADER */}
            <div className="flex items-center justify-between border-b px-6 py-4">
              <div>
                <h2 className="text-2xl font-bold text-slate-800">
                  Detail Kuis
                </h2>

                <p className="text-sm text-slate-500">
                  Preview soal kuis pembelajaran
                </p>
              </div>

              <button
                type="button"
                onClick={() =>
                  setShowDetailModal(false)
                }
                className="rounded-lg p-2 hover:bg-slate-100"
              >
                <X className="h-5 w-5 text-slate-500" />
              </button>
            </div>

            {/* CONTENT */}
            <div className="p-6 space-y-6">
              {/* INFO KUIS */}
              <div className="bg-slate-50 rounded-2xl p-5 border border-slate-200">
                <h3 className="text-xl font-bold text-slate-800">
                  {detailKuis.judul}
                </h3>

                <p className="mt-2 text-slate-600">
                  {detailKuis.deskripsi}
                </p>

                <div className="mt-4 flex flex-wrap gap-3">
                  <div className="inline-flex items-center gap-2 rounded-full bg-blue-100 text-blue-700 px-4 py-2 text-sm font-semibold">
                    <BookOpen className="h-4 w-4" />
                    {detailKuis.total_soal} Soal
                  </div>

                  <div
                    className={`inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold ${
                      detailKuis.status ===
                      "aktif"
                        ? "bg-green-100 text-green-700"
                        : "bg-yellow-100 text-yellow-700"
                    }`}
                  >
                    {detailKuis.status}
                  </div>
                </div>
              </div>

              {/* LIST SOAL */}
              <div className="space-y-5">
                {detailKuis.detail_kuis?.map(
                  (soal, index) => (
                    <div
                      key={
                        soal.id_detail_kuis ||
                        index
                      }
                      className="rounded-2xl border border-slate-200 p-5"
                    >
                      <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-bold text-slate-800">
                          Soal {index + 1}
                        </h3>

                        <div className="text-sm font-semibold text-blue-600">
                          Jawaban:{" "}
                          {soal.jawaban}
                        </div>
                      </div>

                      {/* PERTANYAAN */}
                      <div className="mb-5">
                        <p className="text-slate-700 leading-relaxed">
                          {soal.pertanyaan}
                        </p>

                        {soal.gambar_pertanyaan && (
                          <img
                            src={
                              soal.gambar_pertanyaan
                            }
                            alt={`Gambar soal ${
                              index + 1
                            }`}
                            className="mt-4 max-h-72 max-w-full rounded-xl border border-slate-200 object-contain"
                          />
                        )}
                      </div>

                      {/* PILIHAN */}
                      <div className="grid md:grid-cols-2 gap-4">
                        {[
                          "A",
                          "B",
                          "C",
                          "D",
                        ].map((option) => {
                          const text =
                            soal[
                              `pilihan_${option.toLowerCase()}`
                            ];

                          const image =
                            soal[
                              `gambar_pilihan_${option.toLowerCase()}`
                            ];

                          return (
                            <div
                              key={option}
                              className={`rounded-xl border p-4 ${
                                soal.jawaban ===
                                option
                                  ? "border-green-500 bg-green-50"
                                  : "border-slate-200"
                              }`}
                            >
                              <div>
                                <span className="font-bold">
                                  {option}.
                                </span>{" "}
                                {text}
                              </div>

                              {image && (
                                <img
                                  src={image}
                                  alt={`Pilihan ${option}`}
                                  className="mt-3 max-h-40 max-w-full rounded-lg object-contain"
                                />
                              )}
                            </div>
                          );
                        })}
                      </div>
                    </div>
                  )
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* =========================
          PENGATURAN JUMLAH SOAL
      ========================= */}
      {showPengaturan && (
        <PengaturanNilai
          onClose={() =>
            setShowPengaturan(false)
          }
        />
      )}
    </div>
  );
}
