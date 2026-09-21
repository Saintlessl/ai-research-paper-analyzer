import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { 
  NeuCard, NeuButton, NeuInput, ThemeToggle, 
  NeuTextarea, NeuSelect, NeuSearch, NeuCheckbox, NeuToggle, NeuFileDropzone,
  NeuTabs, NeuPagination, NeuIconButton, NeuDropdown,
  NeuTable, NeuTableRow, NeuBadge, NeuSeverityBadge, NeuStatusPill, NeuAvatar, ScoreGauge, ScoreBar,
  NeuModal, NeuDrawer, NeuTooltip, NeuToast,
  NeuSkeleton, NeuEmptyState, NeuErrorState, NeuProgressBar
} from '@/Components/ui/index';
import { AlertCircle, User, Settings, Check, Mail, Image as ImageIcon } from 'lucide-react';

export default function DesignSystem() {
  const [modalOpen, setModalOpen] = useState(false);
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const [activeTab, setActiveTab] = useState('tab1');
  const [page, setPage] = useState(1);

  return (
    <div className="min-h-screen bg-neu-bg text-neu-text p-8 pb-32 transition-colors duration-200">
      <Head title="Design System" />
      
      <div className="max-w-7xl mx-auto space-y-16">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-4xl font-bold tracking-tight">Neumorphism Design System</h1>
            <p className="mt-2 text-neu-muted">A premium B2B SaaS hybrid soft UI language.</p>
          </div>
          <ThemeToggle />
        </div>

        {/* Tokens */}
        <section className="space-y-6">
          <h2 className="text-2xl font-bold border-b border-neu-hairline pb-2">1. Tokens & Primitives</h2>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <NeuCard elevation="raised-md">
              <h3 className="font-semibold mb-4 text-sm uppercase tracking-wider text-neu-muted">Elevations</h3>
              <div className="space-y-6">
                <div className="h-16 rounded-neu-md neu-flat flex items-center justify-center text-sm font-medium">flat</div>
                <div className="h-16 rounded-neu-md neu-raised-sm flex items-center justify-center text-sm font-medium">raised-sm</div>
                <div className="h-16 rounded-neu-md neu-raised-md flex items-center justify-center text-sm font-medium">raised-md</div>
                <div className="h-16 rounded-neu-md neu-raised-lg flex items-center justify-center text-sm font-medium">raised-lg</div>
                <div className="h-16 rounded-neu-md neu-pressed flex items-center justify-center text-sm font-medium">pressed</div>
              </div>
            </NeuCard>

            <NeuCard elevation="raised-md">
              <h3 className="font-semibold mb-4 text-sm uppercase tracking-wider text-neu-muted">Colors (Base)</h3>
              <div className="space-y-3">
                <div className="flex items-center gap-3"><div className="w-8 h-8 rounded-full bg-neu-bg ring-1 ring-neu-hairline" /> <span className="text-sm">neu-bg</span></div>
                <div className="flex items-center gap-3"><div className="w-8 h-8 rounded-full bg-neu-surface ring-1 ring-neu-hairline" /> <span className="text-sm">neu-surface</span></div>
                <div className="flex items-center gap-3"><div className="w-8 h-8 rounded-full bg-neu-accent ring-1 ring-neu-hairline" /> <span className="text-sm">neu-accent</span></div>
                <div className="flex items-center gap-3"><div className="w-8 h-8 rounded-full bg-neu-text ring-1 ring-neu-hairline" /> <span className="text-sm">neu-text</span></div>
                <div className="flex items-center gap-3"><div className="w-8 h-8 rounded-full bg-neu-muted ring-1 ring-neu-hairline" /> <span className="text-sm">neu-muted</span></div>
              </div>
            </NeuCard>

            <NeuCard elevation="raised-md">
              <h3 className="font-semibold mb-4 text-sm uppercase tracking-wider text-neu-muted">Colors (Status/Severity)</h3>
              <div className="space-y-3">
                <div className="flex items-center gap-3"><div className="w-8 h-8 rounded-full bg-severity-low ring-1 ring-neu-hairline" /> <span className="text-sm">LOW / UPLOADED</span></div>
                <div className="flex items-center gap-3"><div className="w-8 h-8 rounded-full bg-severity-medium ring-1 ring-neu-hairline" /> <span className="text-sm">MEDIUM / PROCESSING</span></div>
                <div className="flex items-center gap-3"><div className="w-8 h-8 rounded-full bg-severity-high ring-1 ring-neu-hairline" /> <span className="text-sm">HIGH</span></div>
                <div className="flex items-center gap-3"><div className="w-8 h-8 rounded-full bg-severity-critical ring-1 ring-neu-hairline" /> <span className="text-sm">CRITICAL / FAILED</span></div>
                <div className="flex items-center gap-3"><div className="w-8 h-8 rounded-full bg-status-analyzed ring-1 ring-neu-hairline" /> <span className="text-sm">ANALYZED / ACCEPT</span></div>
              </div>
            </NeuCard>

            <NeuCard elevation="raised-md">
              <h3 className="font-semibold mb-4 text-sm uppercase tracking-wider text-neu-muted">Typography</h3>
              <div className="space-y-4">
                <div className="font-sans">
                  <p className="text-2xl font-bold tracking-tight">Sans UI (Inter)</p>
                  <p className="text-sm text-neu-muted mt-1">Used for interfaces, dashboards, labels, and buttons.</p>
                </div>
                <div className="font-serif mt-6">
                  <p className="text-2xl font-semibold">Serif Content</p>
                  <p className="text-sm text-neu-muted mt-1 leading-relaxed">Used exclusively for long-form academic reading, abstracts, citations, and AI reviewer reports. Increases perceived value.</p>
                </div>
                <div className="mt-4">
                  <p className="text-lg tabular-nums tracking-wider text-neu-accent font-bold">123,456.78</p>
                  <p className="text-xs text-neu-muted mt-1">Tabular nums for stats/scores</p>
                </div>
              </div>
            </NeuCard>
          </div>
        </section>

        {/* Buttons */}
        <section className="space-y-6">
          <h2 className="text-2xl font-bold border-b border-neu-hairline pb-2">2. Buttons & Actions</h2>
          <NeuCard className="space-y-8">
            <div className="flex flex-wrap gap-6 items-end">
              <div className="space-y-2"><p className="text-xs text-neu-muted">Primary</p><NeuButton variant="primary">Submit Report</NeuButton></div>
              <div className="space-y-2"><p className="text-xs text-neu-muted">Secondary (Default)</p><NeuButton variant="secondary">Cancel</NeuButton></div>
              <div className="space-y-2"><p className="text-xs text-neu-muted">Ghost</p><NeuButton variant="ghost">View Details</NeuButton></div>
              <div className="space-y-2"><p className="text-xs text-neu-muted">Danger</p><NeuButton variant="danger">Delete Paper</NeuButton></div>
            </div>
            
            <div className="flex flex-wrap gap-6 items-end">
              <div className="space-y-2"><p className="text-xs text-neu-muted">Small</p><NeuButton size="sm">Action</NeuButton></div>
              <div className="space-y-2"><p className="text-xs text-neu-muted">Large</p><NeuButton size="lg">Generate AI Review</NeuButton></div>
              <div className="space-y-2"><p className="text-xs text-neu-muted">Icon</p><NeuIconButton icon={<Settings className="h-5 w-5" />} /></div>
            </div>

            <div className="flex flex-wrap gap-6 items-end">
              <div className="space-y-2"><p className="text-xs text-neu-muted">Disabled</p><NeuButton disabled>Cannot click</NeuButton></div>
              <div className="space-y-2"><p className="text-xs text-neu-muted">Loading</p><NeuButton loading variant="primary">Processing...</NeuButton></div>
            </div>
          </NeuCard>
        </section>

        {/* Forms */}
        <section className="space-y-6">
          <h2 className="text-2xl font-bold border-b border-neu-hairline pb-2">3. Form Controls</h2>
          <NeuCard className="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div className="space-y-6">
              <div className="space-y-2">
                <label className="text-sm font-semibold">Standard Input</label>
                <NeuInput placeholder="Enter your email..." />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-semibold">Error State</label>
                <NeuInput error defaultValue="invalid@email" />
                <p className="text-xs text-status-failed">Invalid email format.</p>
              </div>
              <div className="space-y-2">
                <label className="text-sm font-semibold">Search Input</label>
                <NeuSearch placeholder="Search papers, authors..." />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-semibold">Select Dropdown</label>
                <NeuSelect>
                  <option>Machine Learning</option>
                  <option>Computer Vision</option>
                  <option>NLP</option>
                </NeuSelect>
              </div>
            </div>
            
            <div className="space-y-6">
              <div className="space-y-2">
                <label className="text-sm font-semibold">Textarea</label>
                <NeuTextarea placeholder="Write your review summary here..." rows={4} />
              </div>
              
              <div className="flex gap-8">
                <div className="space-y-3">
                  <label className="text-sm font-semibold block">Checkbox</label>
                  <label className="flex items-center gap-3"><NeuCheckbox defaultChecked /> <span className="text-sm">Include citations</span></label>
                  <label className="flex items-center gap-3"><NeuCheckbox /> <span className="text-sm">Match methodology</span></label>
                </div>
                
                <div className="space-y-3">
                  <label className="text-sm font-semibold block">Toggle</label>
                  <label className="flex items-center gap-3"><NeuToggle defaultChecked /> <span className="text-sm">Dark Mode</span></label>
                  <label className="flex items-center gap-3"><NeuToggle /> <span className="text-sm">Notifications</span></label>
                </div>
              </div>
            </div>
            
            <div className="col-span-1 lg:col-span-2">
              <label className="text-sm font-semibold block mb-2">File Dropzone</label>
              <NeuFileDropzone accept=".pdf" />
            </div>
          </NeuCard>
        </section>

        {/* Data & Indicators */}
        <section className="space-y-6">
          <h2 className="text-2xl font-bold border-b border-neu-hairline pb-2">4. Data & Indicators</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            <NeuCard className="space-y-6">
              <h3 className="font-semibold text-sm text-neu-muted mb-4">Badges & Pills</h3>
              <div className="flex flex-wrap gap-3">
                <NeuBadge>v2.4.0</NeuBadge>
                <NeuBadge className="bg-neu-accent text-neu-accent-text">New</NeuBadge>
              </div>
              
              <div className="flex flex-wrap gap-3">
                <NeuSeverityBadge level="LOW" />
                <NeuSeverityBadge level="MEDIUM" />
                <NeuSeverityBadge level="HIGH" />
                <NeuSeverityBadge level="CRITICAL" />
              </div>

              <div className="flex flex-wrap gap-3">
                <NeuStatusPill status="UPLOADED" />
                <NeuStatusPill status="PROCESSING" />
                <NeuStatusPill status="ANALYZED" />
                <NeuStatusPill status="FAILED" />
                <NeuStatusPill status="ARCHIVED" />
              </div>

              <h3 className="font-semibold text-sm text-neu-muted mt-8 mb-4">Avatars</h3>
              <div className="flex items-center gap-4">
                <NeuAvatar name="Admin User" size="sm" />
                <NeuAvatar name="John Doe" size="md" />
                <NeuAvatar name="Sarah Connor" size="lg" src="https://i.pravatar.cc/150?u=1" />
              </div>
            </NeuCard>

            <NeuCard className="space-y-8">
              <h3 className="font-semibold text-sm text-neu-muted mb-4">Scoring</h3>
              <div className="flex justify-around items-end">
                <ScoreGauge score={87} label="Overall" />
                <ScoreGauge score={42} label="Methodology" />
                <ScoreGauge score={0} label="Empty" />
              </div>
              
              <div className="space-y-4 pt-4 border-t border-neu-hairline">
                <ScoreBar score={92} label="Clarity" />
                <ScoreBar score={65} label="Originality" />
                <ScoreBar score={15} label="Formatting" />
              </div>
            </NeuCard>
          </div>
        </section>

        {/* Navigation & Overlay */}
        <section className="space-y-6">
          <h2 className="text-2xl font-bold border-b border-neu-hairline pb-2">5. Navigation & Overlay</h2>
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <NeuCard className="space-y-8">
              <div>
                <h3 className="font-semibold text-sm text-neu-muted mb-4">Tabs</h3>
                <NeuTabs 
                  tabs={[{id:'tab1', label:'Overview'}, {id:'tab2', label:'Analysis'}, {id:'tab3', label:'Scores'}]}
                  activeId={activeTab}
                  onChange={setActiveTab}
                />
              </div>

              <div>
                <h3 className="font-semibold text-sm text-neu-muted mb-4">Pagination</h3>
                <NeuPagination currentPage={page} totalPages={12} onPageChange={setPage} />
              </div>

              <div>
                <h3 className="font-semibold text-sm text-neu-muted mb-4">Dropdown & Tooltip</h3>
                <div className="flex items-center gap-8">
                  <NeuDropdown 
                    isOpen={dropdownOpen} 
                    setIsOpen={setDropdownOpen}
                    trigger={<NeuButton>Open Menu</NeuButton>}
                  >
                    <div className="px-4 py-2 hover:bg-neu-pressed cursor-pointer text-sm">Profile Settings</div>
                    <div className="px-4 py-2 hover:bg-neu-pressed cursor-pointer text-sm">Documentation</div>
                    <div className="px-4 py-2 hover:bg-neu-pressed cursor-pointer text-sm text-status-failed border-t border-neu-hairline mt-1">Sign Out</div>
                  </NeuDropdown>

                  <NeuTooltip content="This is a neumorphic tooltip that gives extra context.">
                    <span className="flex items-center gap-1 text-sm font-semibold text-neu-muted border-b border-dashed border-neu-muted cursor-help">Hover me</span>
                  </NeuTooltip>
                </div>
              </div>
            </NeuCard>

            <NeuCard className="space-y-8 bg-transparent" elevation="flat">
              <h3 className="font-semibold text-sm text-neu-muted mb-4">Overlays & Modals</h3>
              <div className="flex flex-wrap gap-4">
                <NeuButton onClick={() => setModalOpen(true)}>Open Modal</NeuButton>
                <NeuButton onClick={() => setDrawerOpen(true)}>Open Drawer</NeuButton>
              </div>

              <h3 className="font-semibold text-sm text-neu-muted mt-8 mb-4">Toast Notification (Static)</h3>
              <NeuToast title="Paper analyzed successfully" message="The AI review report is now available in your workspace." type="success" />
              <NeuToast title="Connection error" message="Failed to reach the Gemini API." type="error" />
            </NeuCard>
          </div>
        </section>

        {/* States */}
        <section className="space-y-6">
          <h2 className="text-2xl font-bold border-b border-neu-hairline pb-2">6. UI States & Tables</h2>
          
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <NeuCard className="space-y-6">
              <h3 className="font-semibold text-sm text-neu-muted mb-4">Loading / Skeleton</h3>
              <div className="flex gap-4">
                <NeuSkeleton className="h-12 w-12 rounded-full" />
                <div className="space-y-2 flex-1">
                  <NeuSkeleton className="h-4 w-3/4" />
                  <NeuSkeleton className="h-4 w-1/2" />
                </div>
              </div>

              <h3 className="font-semibold text-sm text-neu-muted mt-8 mb-4">Progress Bar</h3>
              <NeuProgressBar value={45} label="Extracting PDF chunks..." />
              <div className="mt-4"><NeuProgressBar value={100} label="Completed" /></div>
            </NeuCard>

            <div className="space-y-8">
              <NeuEmptyState 
                title="No papers found" 
                description="You haven't uploaded any research papers yet. Upload one to get started."
                action={<NeuButton variant="primary">Upload Paper</NeuButton>}
                icon={<ImageIcon className="h-8 w-8" />}
              />
              
              <NeuErrorState 
                message="The AI engine timed out while processing this document. It might be too large or encrypted."
                onRetry={() => alert('Retrying...')}
              />
            </div>
          </div>

          <div className="pt-8">
            <h3 className="font-semibold text-sm text-neu-muted mb-4">Data Table (Flat rows in Raised container)</h3>
            <NeuTable headers={['Paper Title', 'Domain', 'Status', 'Score', 'Actions']}>
              <NeuTableRow>
                <td className="px-6 py-4 font-semibold">Attention Is All You Need</td>
                <td className="px-6 py-4 text-neu-muted">Machine Learning</td>
                <td className="px-6 py-4"><NeuStatusPill status="ANALYZED" /></td>
                <td className="px-6 py-4 font-bold tabular-nums">98/100</td>
                <td className="px-6 py-4"><NeuButton size="sm">View</NeuButton></td>
              </NeuTableRow>
              <NeuTableRow>
                <td className="px-6 py-4 font-semibold">BERT: Pre-training of Deep Bidirectional Transformers</td>
                <td className="px-6 py-4 text-neu-muted">NLP</td>
                <td className="px-6 py-4"><NeuStatusPill status="PROCESSING" /></td>
                <td className="px-6 py-4 font-bold tabular-nums text-neu-muted">—</td>
                <td className="px-6 py-4"><NeuButton size="sm" disabled>View</NeuButton></td>
              </NeuTableRow>
              <NeuTableRow>
                <td className="px-6 py-4 font-semibold">ImageNet Classification with Deep Convolutional Neural Networks</td>
                <td className="px-6 py-4 text-neu-muted">Computer Vision</td>
                <td className="px-6 py-4"><NeuStatusPill status="FAILED" /></td>
                <td className="px-6 py-4 font-bold tabular-nums text-neu-muted">—</td>
                <td className="px-6 py-4"><NeuButton size="sm" variant="danger">Retry</NeuButton></td>
              </NeuTableRow>
            </NeuTable>
          </div>
        </section>

      </div>

      {/* Render Portal Modals */}
      <NeuModal 
        isOpen={modalOpen} 
        onClose={() => setModalOpen(false)} 
        title="Confirm Deletion"
        footer={<><NeuButton onClick={() => setModalOpen(false)}>Cancel</NeuButton><NeuButton variant="danger">Delete Permanently</NeuButton></>}
      >
        <p className="text-neu-muted leading-relaxed">Are you sure you want to delete this paper? This action cannot be undone and will permanently remove the PDF from secure storage.</p>
      </NeuModal>

      <NeuDrawer 
        isOpen={drawerOpen} 
        onClose={() => setDrawerOpen(false)} 
        title="Settings"
      >
        <div className="space-y-6">
          <p className="text-sm text-neu-muted">Configure your workspace preferences.</p>
          <div className="space-y-4">
            <label className="flex items-center justify-between"><span className="text-sm font-semibold">Email Notifications</span> <NeuToggle defaultChecked /></label>
            <label className="flex items-center justify-between"><span className="text-sm font-semibold">Auto-assign AI Reviewer</span> <NeuToggle defaultChecked /></label>
            <label className="flex items-center justify-between"><span className="text-sm font-semibold">Compact Table View</span> <NeuToggle /></label>
          </div>
        </div>
      </NeuDrawer>
    </div>
  );
}
