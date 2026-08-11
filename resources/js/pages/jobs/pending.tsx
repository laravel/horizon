import { JobList, type JobListProps } from "@/components/jobs/job-list";
export default (props: Omit<JobListProps, "title" | "type">) => (
    <JobList {...props} title="Pending Jobs" type="pending" />
);
