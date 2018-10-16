import React, {Component} from 'react';
import PreAlignStatus from "./PreAlignStatus/PreAlignStatus.component";
import Animation from "./Animation/Animation.component";
import AlignmentScoreComponent from "./AlignmentScore/AlignmentScore.component";
import SegmentAlignedComponent from "./SegmentAligned/SegmentAligned.component";
import ScurceComponent from "./Source/Source.component";
import TargetComponent from "./Target/Trget.component";


class AnalyseComponent extends Component {
    static propTypes = {};

    constructor(props) {
        super(props);
    }

    render() {
        return (
            <div className="container analyse">
                {/* <div id="title">
                    <h1>Matecat's intelligence is currently aligning your files, please wait</h1>
                </div>
                <PreAlignStatus props = {this.props}/>
                <Animation/> */}
                <div className="files-info">
                    <ScurceComponent/>
                    <TargetComponent/>
                </div>

                <PreAlignStatus props = {this.props}/>

                <div className="process-info">
                    <SegmentAlignedComponent/>
                   <AlignmentScoreComponent/>
                </div>
                <Animation/>
            </div>
        );
    }
}

export default AnalyseComponent;
