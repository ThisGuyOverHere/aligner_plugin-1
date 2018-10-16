import * as React from 'react'
import SegmentComponent from '../Row/Segment/Segment.component'
import SystemActions from "../../../Actions/System.actions";
import ProjectActions from "../../../Actions/Project.actions";

export default class SegmentDragLayer extends React.PureComponent {

    constructor(props) {
        super(props);
        this.state = {
            animate: false
        }
    }

    componentDidMount() {
        setTimeout(this.animate,10);
        SystemActions.setInDrag(true);
    }
    componentWillUnmount(){
        SystemActions.setInDrag(false);
    }

    render() {
        return (
            <div className="dragSegmentContainer" style={this.getStyles()}>
               {/* <SegmentComponent {...this.props.item} />*/}
            </div>
        )
    }

    getStyles = () =>{
        const transform = this.state.animate ? {
            transform: 'rotate(-3deg) scale(.7)',
            WebkitTransform: 'rotate(-3deg) scale(.7)',
        }: {};
        return {
            display: 'inline-block',
            width: '100%',
            ...transform
        }
    };
    animate = () => {
        this.setState({
            animate: true
        })
    };


}
